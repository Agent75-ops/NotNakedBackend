<?php

namespace Tests\Feature;

use App\Http\Controllers\HelperController;
use App\Models\Favorite;
use App\Models\FavoritesList;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;
use Tests\Feature\HelperTest;
use Illuminate\Http\UploadedFile;

class FavoritesListTest extends TestCase
{
    use RefreshDatabase;

    public $user_1;
    public $user_2;
    public $token_1;
    public $token_2;
    public $product_1;
    public $product_2;
    public $product_3;
    public $favorites_list_1;
    public $favorites_list_2;
    public $favorites_list_3;
    public $favorites_list_4;
    public $favorite_1;

    public function setUp():void
    {
        parent::setUp();
        $this->seed(UserRolePermissionSeeder::class);
        // create_users 
        $users = HelperTest::create_users();
        User::create(['name'=>"anonymous", 'email'=>"an@email.com",'password'=>"234"]);
        $this->user_1 = $users['users'][0];
        $this->user_2 = $users['users'][1];
        $user_3 = $users["users"][2];

        $this->token_1 = $users['tokens'][0];
        $this->token_2 = $users['tokens'][1];
        $token_3 = $users['tokens'][2];

        // create_products
        $products = HelperTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $this->product_3 = $products[2];

        // create a FavoritesList for user_1
        $this->favorites_list_1 = FavoritesList::create([
            'user_id' => $this->user_1->id,
            'name' => $this->user_1->name . "'s Favorites",
            'views_count'=>0,
            'likes_count'=>0,
            'thumbnail' => null
        ]);
        $this->favorite_1 = Favorite::create([
            'favorites_list_id' => $this->favorites_list_1->id, 
            'product_id' => $this->product_1->id
        ]);

        // create a FavoritesList for user_2
        $this->favorites_list_2 =FavoritesList::create([
            'user_id' => $this->user_2->id,
            'name' => $this->user_2->name . "'s Favorites",
            'views_count'=>3,
            'likes_count'=>3,
            'thumbnail' => null
            //ratio:-9
        ]);
        Favorite::create([
            'favorites_list_id' =>$this->favorites_list_2->id, 
            'product_id' => $this->product_2->id
        ]);

        //create a FavroitesList for user_3
        $this->favorites_list_3=FavoritesList::create([
            'user_id' => $user_3->id,
            'name' => $user_3->name . "'s Favorites",
            'views_count'=>6,
            'likes_count'=>2,
            'thumbnail' => null
            //ratio : -2

        ]);
        Favorite::create([
            'favorites_list_id' =>$this->favorites_list_3->id, 
            'product_id' => $this->product_3->id
        ]);

        //create a FavroitesList for user_4
        $user_4 = User::create(['name'=>"askf", 'email'=>"ansdf@email.com",'password'=>"234"]);
        $this->favorites_list_4=FavoritesList::create([
            'user_id' => $user_4->id,
            'name' => $user_4->name. "'s Favorites",
            'views_count'=>6,
            'likes_count'=>2
        ]);
    }
    

    // likeFavoritesList method 
    public function test_like_favorites_list():void
    {
        $old_likes_count = $this->favorites_list_1->likes_count;
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/like',[],$headers);
        $this->favorites_list_1->refresh();
        // $request->assertOk();
        // $this->assertEquals($old_likes_count+1 , $this->favorites_list_1->likes_count);
        $data = ["action" =>"liked"];
        $metadata = ['likes_count' => $old_likes_count+1];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }
    
    public function test_like_favorites_list_again():void
    {   
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/like',[],$headers);
        $this->favorites_list_1->refresh();
        $old_likes_count = $this->favorites_list_1->likes_count;
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/like',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertOk();
        $this->assertEquals($old_likes_count-1 , $this->favorites_list_1->likes_count);
        $data = ["action" =>"unliked"];
        $metadata = ['likes_count' => $old_likes_count-1];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_like_favorites_list_does_not_exist():void
    {
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $request = $this->postJson('api/favorites_lists/324324/like',[],$headers);
        $request->assertNotFound();
        $error = ["message" =>"Favorites list not found.",'code'=>404];
        $request->assertJson(HelperTest::getFailedResponse($error,[]));
    }

    public function test_like_favorites_list_unauthorized():void
    {
        $old_likes_count = $this->favorites_list_1->likes_count;
        $headers = [];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/like',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertUnauthorized();
        $request->assertJson(['message' => "Unauthenticated."]);
        $this->assertEquals($old_likes_count , $this->favorites_list_1->likes_count);
    }

    // viewFavoritesList method 
    public function test_view_favorites_list():void
    {
        $old_views_count = $this->favorites_list_1->views_count;
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertOk();
        $data = ["action" =>"viewed"];
        $metadata = ['views_count' => $old_views_count+1];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_view_favorites_list_again():void
    {
        $old_views_count = $this->favorites_list_1->views_count;
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertOk();
        $this->assertEquals($old_views_count+1, $this->favorites_list_1->views_count);
        $data = ['action' => 'viewed' ];
        $metadata = ["views_count"=>$old_views_count+1];
        $request->assertJson(HelperTest::getSuccessResponse($data, $metadata));
    }

    public function test_view_favorites_list_unauthorized():void
    {
        $old_views_count = $this->favorites_list_1->views_count;
        $headers = [];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertOk();
        $this->assertEquals($old_views_count+1, $this->favorites_list_1->views_count);
        $data = ['action' => 'viewed' ];
        $metadata = ["views_count"=>$old_views_count+1];
        $request->assertJson(HelperTest::getSuccessResponse($data, $metadata));
    }

    //admin and super-admin views don't count
    public function test_view_favorites_list_by_admin_and_super_admin():void
    {
        //create an admin account
        $admin_user_token = HelperTest::create_admin();
        $admin = $admin_user_token['user'];
        $token_admin = $admin_user_token['token'];
    
        //create super admin account
        $admin_user_token = HelperTest::create_super_admin();
        $super_admin = $admin_user_token['user'];
        $token_super_admin =  $admin_user_token['token'];
    
        //view by admin
        $headers = ["Authorization" => "Bearer " . $token_admin];
        $old_views_count = $this->favorites_list_1->views_count;
        
        $admin_view = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $admin_view->assertOk();
        $this->favorites_list_1->refresh();

        $this->assertEquals($old_views_count,$this->favorites_list_1->views_count);
        $data = ['action' => 'viewed' ];
        $metadata = ['views_count'=>0];
        $admin_view->assertJson(HelperTest::getSuccessResponse($data, $metadata));

        //view by super-admin
        $headers = ["Authorization" => "Bearer " . $token_super_admin];
        $super_admin_view = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $super_admin_view->assertOk();
        $this->favorites_list_1->refresh();

        $this->assertEquals($old_views_count,$this->favorites_list_1->views_count);
        $data = ['action' => 'viewed' ];
        $metadata = ['views_count'=>0];
        $super_admin_view->assertJson(HelperTest::getSuccessResponse($data, $metadata));
    }

    // // retreiveById method 

    public function test_retrieve_favorites_list_by_id():void
    {
        $request = $this->getJson("api/favorites_lists/". $this->favorites_list_1->id);
        $request->assertOk();
        $request->assertJsonStructure([
            "metadata",
            'error',
            'status',
            "data"=>[
                'favorites list'=>[
                    "id",
                    "name",
                    "created_at",
                    "views_count",
                    "likes_count",
                    'public', 
                    'user_id', 
                    "user",
                    'is_liked_by_current_user'
                ]
            ]
        ]);
    }

    public function test_retrieve_favorites_list_by_id_not_found():void
    {
        $request = $this->getJson("api/favorites_lists/"  . 324324);
        $request->assertNotFound();

        $error = [
            'message'=>'Favorites list not found.',
            'code'=>404
        ];
        $request->assertJson(HelperTest::getFailedResponse($error, null));
    }

    // retrieveByUser method 

    public function test_retrieve_favorites_list_by_user():void
    {
        $headers = ["Authorization" => "Bearer " .$this->token_1];
        $request = $this->getJson("api/users/user/favorites_lists",$headers);
        $request->assertOk();
        $request->assertJsonFragment(['id'=>$this->user_1->id]);
        $request->assertJsonStructure([
            'metadata',
            'error',
            "data"=>[
                'favorites list'=>[
                    "id",
                    'name',
                    'likes_count',
                    'views_count',
                    'public',
                    'created_at',
                    'user_id',
                    'is_liked_by_current_user'
                ]
            ]
        ]);
    }

    public function test_retrieve_favorites_list_by_user_unauthenticated():void
    {
        $headers =[];
        $request = $this->getJson("api/users/user/favorites_lists", $headers);
        $request->assertUnauthorized();
        $request->assertJson(["message"=>"Unauthenticated."]);
    }

    // // listFavoritesLists method 

    //initially sorted by most_popular (ratio num_views/num_likes)
    public function test_list_favorites_lists():void
    {
        $request = $this->getJson("api/favorites_lists");
        $request->assertOk();
        $data = [ 
            'favorites lists' =>[
                ["id" =>$this->favorites_list_2->id],
                ['id' =>$this->favorites_list_3->id],
                ["id" =>$this->favorites_list_1->id]
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
        $request->assertJsonMissing(["id"=>$this->favorites_list_4->id]);
    }

    public function test_list_favorites_lists_limited():void
    {
        $request = $this->getJson("api/favorites_lists?page=1&limit=1");
        $request->assertOk();
        $metadata = [
            "count"=>1,
            "total_count"=>3,
            "pages_count" => 3, // ciel of total_count/limit 
            "current_page" => 1,
            "limit" => 1,
        ];
        $request->assertJson(HelperTest::getSuccessResponse([],$metadata));
        $request->assertJsonMissing([
            "id"=>$this->favorites_list_4->id
        ]);
    }

    public function test_list_favorites_lists_sort_by_most_viewed():void
    {
        $request= $this->getJson("api/favorites_lists?sort+by=views_count+DESC");
        $request->assertOk();
        $data = [ 
            'favorites lists' =>[
                ['id' =>$this->favorites_list_3->id],
                ["id" =>$this->favorites_list_2->id],
                ["id" =>$this->favorites_list_1->id]
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_list_favorites_lists_sort_by_most_liked():void
    {
        $request= $this->getJson("api/favorites_lists?sort-by=likes_count-DESC");
        $request->assertOk();
        $data = [ 
            'favorites lists' =>[
                ['id' =>$this->favorites_list_2->id],
                ["id" =>$this->favorites_list_3->id],
                ["id" =>$this->favorites_list_1->id]
            ]
        ];
        $metadata = [
            "count"=>3,
            "total_count"=>3,
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    public function test_list_favorites_lists_search():void
    {
        $request= $this->getJson("api/favorites_lists?q=abc");
        $request->assertOk();
        $data = [ 
            'favorites lists' =>[
                ['id' =>$this->favorites_list_2->id,],
                ["id" =>$this->favorites_list_1->id]
            ]
        ];
        $metadata = [
            "count"=>2, // count after pagination (limit)
            "total_count"=>2, // total count in search result
            "pages_count" => 1, 
            "current_page" => 1,
            "limit" => 50,
        ];
        $request->assertJson(HelperTest::getSuccessResponse($data,$metadata));
    }

    // // updateFavoritesList method

    public function test_update_favorites_list_name():void
    {
        $headers = ["Authorization" => "Bearer " .$this->token_1];
        $body = ['name' => 'Summer List'];
        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$body,$headers);
        $this->favorites_list_1->refresh();

        $data = ['favorites list'=> ['name'=>'Summer List']];
        $request->assertOk();
        $request->assertJson(HelperTest::getSuccessResponse($data,null));
        $this->assertEquals('Summer List' , $this->favorites_list_1->name);
    }   

    public function test_update_favorites_list_id():void
    {
        $headers = ["Authorization" => "Bearer " . $this->token_1]; 
        $data = ['id'=>324,'name'=>"Winter List"];
        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$data,$headers);
        $request->assertBadRequest();
        $error = [
            "message"=>"Validation error.",
            "code" => 400,
            "details"=> [
                "id"=> [
                    "You do not have the required authorization to update this field."
                ]
            ]
        ];
        $metadata  = ["error_fields"=>['id']];
        $request->assertJson(HelperTest::getFailedResponse($error,$metadata));
        $this->assertNotEquals('Summer List' , $this->favorites_list_1->name); 
    }

    public function test_update_favorites_list_no_fields_to_update():void
    {
        $headers = ["Authorization" => "Bearer " . $this->token_1]; 
        $data = [];
        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$data,$headers);
        $request->assertJson(HelperTest::getSuccessResponse(null, null));
        $request->assertOk();
    }
    
    public function test_update_favorites_list_name_wrong_user():void
    {
        $headers = ["Authorization" => "Bearer " .$this->token_2]; //user 2 updating user 1's list
        $data = ['name' => 'Summer List'];
        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$data,$headers);
        $request->assertForbidden();
        $error = [
            "message" => 'You do not have the required authorization.',
            'code'=>403
        ];
        $request->assertJson(HelperTest::getFailedResponse($error,null));
        $this->assertNotEquals('Summer List' , $this->favorites_list_1->name); 
    }

    public function test_update_favorites_list_by_admin():void
    {
        //create an admin account
        $admin_user_token = HelperTest::create_admin();
        $token_admin = $admin_user_token['token'];

        $headers = ["Authorization" => "Bearer " . $token_admin]; 
        $data = ['name' => 'Summer List'];
        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$data,$headers);
        $request->assertOk();

        $data = ['favorites list' => ["name" => 'Summer List']];
        $request->assertJson(HelperTest::getSuccessResponse($data,null));
        $this->favorites_list_1->refresh();
        $this->assertEquals('Summer List' , $this->favorites_list_1->name); 
    }

    
    public function test_update_favorites_list_name_and_thumbnail():void
    {   
        // default profile picture initially
        $this->assertTrue(str_ends_with($this->favorites_list_1->thumbnail,'defaultFavoritesListThumbnail.png')); 
        
        // Create a fake image file
        $fakeImage = UploadedFile::fake()->image('test_image.jpg');

        $headers = ["Authorization" => "Bearer " . $this->token_1]; 
        $body = ['name' => 'Summer List' , 'thumbnail' =>$fakeImage];

        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$body,$headers);
        $request->assertOk();

       
        $expected_json_struc = [
            'status',
            'data' => ['favorites list' =>[
                'name', 'thumbnail'
            ]],
            'metadata',
            'error',
        ];
        $request->assertJsonStructure($expected_json_struc);
        $request->assertJsonFragment(["name"=>"Summer List"]);
        
        // /public/storage/favoritesListsThumbnails points to /storage/app/public/favoritesListsThumbnails
        $expected_thumbnail = env('APP_URL') . '/storage/favoritesListsThumbnails/';
        $returned_thumbnail =$request->json()['data']['favorites list']['thumbnail'];
        $this->assertTrue(strpos($returned_thumbnail,$expected_thumbnail) === 0);
    }

    public function test_update_favorites_list_public():void
    {
        $headers = ["Authorization" => "Bearer " .$this->token_1];
        $data = ['public' => false];
        $request = $this->patchJson('/api/favorites_lists/'.$this->favorites_list_1->id,$data,$headers);
        $request->assertOk();
        $data =['favorites list'=>['public'=>false]];
        $request->assertJson(HelperTest::getSuccessResponse($data,null));
    }

    public function test_update_favorites_list_does_not_exist():void
    {
        $headers = ["Authorization" => "Bearer " .$this->token_1];
        $data = ['public' => false];
        $request = $this->patchJson('/api/favorites_lists/3294230',$data,$headers);
        $request->assertNotFound();
        $error = ['message'=>'Favorites list not found.', 'code' => 404];
        $request->assertJson(HelperTest::getFailedResponse($error,null));
    }

    // [
    //     {
    //         id:1,
    //         name:"John's Favorites",
    //         views_count:213,
    //         likes_count:234,
    //         thumbnail:"url",
    //         user_id:{
    //             id:1,
    //             profile_picture:Hoody2,
    //             name:"josh",
    //         }
    //     },
    // ]



}
