<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductsImage;

class Product extends Model
{
    use HasFactory;
    protected $appends =[
        'thumbnail'
    ];
    protected $fillable = [
        'category_id' , 
        'name', 
        'created_at',
        'price', 
        'description', 
        'type', 
        'quantity'
    ];
 

     /**
    * Product is child in the relationship 
    * Get the categories. each product has only one category : use belongsTo
    */
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function sales(){
        return $this->hasMany(Sale::class);
    }

    public function colors(){
        return $this->belongsToMany(Color::class,"colors_products", "product_id","color_id");
    }

    public function sizes(){
        return $this->belongsToMany(Size::class, "products_sizes","product_id","size_id");
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function images(){
        return $this->hasMany(ProductsImage::class);
    }

    public function orderDetails(){
        return $this->hasMany(OrderDetail::class);
    }

    public function favorites(){
        return $this->hasMany(Favorite::class);
    }

    public function shoppingCartItems(){
        return $this->hasMany(ShoppingCartItem::class);
    }

    public function getThumbnailAttribute(){
        $thumbnail=  $this->images()->where("is_thumbnail",true)->first();
        if (!$thumbnail){
            $thumbnail = ProductsImage::where("image_url", "defaultProductImage.png")->first();
        }
        return $thumbnail;
    }

    public function getCoverImagesAttribute(){
        return $this->images()->where("is_thumbnail",false)->get();
    }

    // returns all units of alternative sizes + unit of main size of a product (list)
    public function getSizesUnitsAttribute(){
        $First_size =  $this->sizes[0];
        $units =$First_size->alternative_units;
        array_unshift($units, $First_size->unit);
        return $units;
    }
    
    // returns a list of lists of sizes and their alternatives (list of lists)
    public function getSizesListsAttribute(){
        $alt_sizes_array = [];
        $sizes = $this->sizes;
        foreach($sizes as $size){
            $alt_sizes = [$size->size];
            $alt_sizes_of_size = $size->alternativeSizes()->select('size')->orderBy("id")->get()->all();
            foreach($alt_sizes_of_size as $alt_size){
                array_push($alt_sizes, $alt_size->size);
            }
            array_push($alt_sizes_array,$alt_sizes);
        }
        return $alt_sizes_array;
    }

    public function getSizesArrayAttribute(){
        $c=[] ;
        foreach($this->sizes()->get() as $s){
            array_push($c ,$s->size);
        }
        return $c;
    }

    public function getCurrentSaleAttribute(){
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sale = $this->sales()->where([['starts_at' , '<=' ,$now ],['ends_at','>',$now]])->orderBy('starts_at','DESC')->first();
        return $sale;
    }

    public function getCurrentPriceAttribute(){
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sale = $this->sales()->where([['starts_at' , '<=' ,$now ],['ends_at','>',$now]])->orderBy('starts_at','DESC')->first();
        if ($sale) {
            return $sale->price_after_sale;
        }
        return $this->price;
    }


    public function getColorsArrayAttribute(){
        $c=[] ;
        foreach($this->colors()->get() as $col){
            array_push($c ,$col->color);
        }
        return $c;
    }
   
    public function getAverageRatingsAttribute(){
        return $this->reviews->avg("product_rating");
    }

    public function getReviewsCountAttribute(){
        return $this->reviews->count();
    }

}
