<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductFullResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use DateTime;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PhpOption\None;

// get() returns a collection with conditions (where() orderBy()..) : returns QuerySet
// first() returns the first instance                               : returns Object 
// find() finds based on id , find(1) similar to first()            : returns Object
// all() returns a collection without conditions                    : returns QuerySet

// others return object builders (can't be used without using get() after)


class ProductController extends Controller{

    private function priceBetween($products,$price=null){
        if (!$price){
            return $products;
        }
        $price =explode("-", $price);
        $min = $price[0];
        $max = null;
        if (count($price)>=2){
            $max = $price[1];
        }
        if ($min > 0){
            $products = $products->where("price",">=",$min);
        }
        if ($max){
            $products = $products->where("price","<=",$max);
        }
        return $products;
    }
    
    private function filterColor($products,$color){
        if($color){
            $products = $products->whereHas("colors",function($query) use(&$color) {
                $query->where("color", $color);
            });
        }
        return $products;
    }

    private function filterSize($products,$size){
        if($size){
            $products = $products->whereHas("sizes", function($query)use(&$size){
                $query->where("size",$size);
            });
        }
        return $products;
    }
    
    // returns the descendents of a collection of categories 
    public function getCollectionCategoryChildren (array $categoryArr){
        $result = [];
        foreach($categoryArr as $category_name){
            $category_obj = Category::where('category', $category_name)->first();
            $result = array_merge($result , $this->getSingleCategoryChildren($category_obj));
        }
        return $result;
    }

    // returns the descendents of a single category
    public function getSingleCategoryChildren($category){
        if(!($category instanceof Category)) return [];
        //array of children of $category
        $children = Category::where('parent_id', $category->id)->get()->all();
        if (!$children) return [$category]; 
        $resultArr = [];
        foreach($children as $child){
            // get the children of each child
            $resultArr= array_merge($resultArr, $this->getSingleCategoryChildren($child));
        }
        array_push($resultArr,$category);
        return $resultArr; 
    }

    // [men, shoe, running] returns category object named "running" that is a child of shoes that is a child of men
    public function getChildByParents(array $categories){
       
        $array_length = count($categories);
        $parent = $categories[0];
     
        $parent_obj = Category::where('category' , $parent)->first();
        if (!$parent_obj || $array_length == 1) return Category::where("category",$parent)->first();
        $parent_id =$parent_obj->id; 
        $temp= [];
        for ($i = 1 ; $i<$array_length;$i+=1){
            $ctg=Category::where('parent_id',$parent_id)->where("category",$categories[$i])->first();
            array_push($temp, $ctg);

            // category doesn't exist
            if (!$ctg){
                $parent_id = null;
                break;
            }

            $parent_id = $ctg->id;
        }
        return Category::find($parent_id);
    }

    private function filterCategories($products, array $categories=[]){
        //returns all prodcuts starting from the last element in categories[] till the end of the tree
        if (count($categories)<= 0) return $products;
    
        // get the children of every category in $categories
        $categories_array = $this->getSingleCategoryChildren($this->getChildByParents($categories));
        $ids_array = [];
       
        foreach($categories_array as $category){
            array_push($ids_array,$category->id);
        }
    
        // filter based on the children and parents array 
        if ($ids_array && count($ids_array) > 0){
            $products = $products->whereHas("category" , function($query) use (&$ids_array){
                $query->whereIn("id", $ids_array);
            });
        }
        return $products;
    }

    private function filterSales($products,$sales){
        if ($sales == 'true') {
            $products = $products->whereHas("sales", function($query){
                 $query->where('starts_at' , '<', (new DateTime())->format('Y-m-d H:i:s') )->where('ends_at' , '>', (new DateTime())->format('Y-m-d H:i:s'));
                }
            );
        }
        return $products;
    }

    private function filterNewArrivals($products, $new){
        if ($new == 'true') {
            $products = $products->orderBy("created_at","DESC");
        }
        return $products;
    }

    private function filterProducts($products,array $filters){
        $products = $this->priceBetween($products,$filters['price']);
        $products = $this->filterColor($products, $filters['color']);
        $products = $this->filterSize($products,$filters['size']);
        $products = $this->filterCategories($products, $filters['categories']);
        $products = $this->filterSales($products, $filters['sales']);
        $products = $this->filterNewArrivals($products, $filters['new_arrivals']);

        return $products;
    }

    // get all 
    public function listProducts(Request $request){
        $color = $request->input("color");
        $size = $request->input("size");
        $price = $request->input("price");
        $categories= $request->input("categories") ?$request->input("categories"):[];
        $sort_by = $request->input("sort_by");
        $pageLimit = ['page'=>$request->input("page"),'limit'=>$request->input("limit")];
        $sales = $request->input("sales");
        $new_arrivals = $request->input("new-arrivals");
        $search = $request->input("q");
        
        $products=  Product::with([]);
        if ($search){
            $products = $products->where('name','like',"%$search%");
        }

        $filters = [
            'sales'=>$sales,
            'new_arrivals' =>$new_arrivals,
            'color'=>$color,
            'size' =>$size,
            'price'=>$price,
            'categories' =>$categories,
            'sort_by' =>$sort_by,
            'page' =>$pageLimit['page'],
            'limit'=>$pageLimit['limit']
        ];

        $products = $this->filterProducts($products,$filters);

        //pagination applied for filter results 
        $products = HelperController::getCollectionAndCount(
            $products,
            $filters["sort_by"],
            $pageLimit,
            ProductResource::class,
            'products'
        );
        return response($products, 200) ;
    }

    public function listPopularProducts(Request $request){
        $limit = $request->input("limit");
        $page= $request->input("page");
        $search = $request->input("search");

        $products = Product::select("products.*")
        ->leftJoin("order_details", 'products.id', '=','order_details.product_id')
        ->join('orders','order_details.order_id','=','orders.id')
        ->groupBy('products.id')->orderByRaw("sum(order_details.ordered_quantity) DESC");

        //pagination applied for filter results 
        $products = HelperController::getCollectionAndCount(
            $products,
            ['page'=>$page,'limit'=>$limit],
            null,
            ProductResource::class,
            'products'
        );

        return response($products,200);
    }

    public function retrieveProduct(Request $request , $id){
        $product = Product::find($id);
        HelperController::checkIfNotFound($product, 'Product');
        $product_arr = (new ProductFullResource($product))->toArray($request);
        $response = HelperController::getSuccessResponse(['product'=>$product_arr],null);
        return response($response, 200);
    }

    public function productSize(Request $request , $id){
        $product = Product::find($id);
        HelperController::checkIfNotFound($product, 'Product');
        $response = HelperController::getSuccessResponse(['sizes'=>$product->sizes_array],null);
        return response($response,200);
    }

    public function productColor(Request $request ,$id){
        $product = Product::find($id);
        HelperController::checkIfNotFound($product, 'Product');
        $response = HelperController::getSuccessResponse(['sizes'=>$product->colors_array],null);
        return response($response,200);   
    }
}
