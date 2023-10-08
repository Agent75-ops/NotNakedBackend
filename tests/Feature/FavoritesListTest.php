<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\FavoritesList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

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
    public function setUp():void
    {
        parent::setUp();

        // create_users 
        $users =OrderTest::create_users();
        User::create(['id'=>1,'name'=>"anonymous", 'email'=>"an@email.com",'password'=>"234"]);
        $this->user_1 = $users['users'][0];
        $this->user_2 = $users['users'][1];
        $user_3 = $users["users"][2];

        $this->token_1 = $users['tokens'][0];
        $this->token_2 = $users['tokens'][1];
        $token_3 = $users['tokens'][2];

        // create_products
        $products = OrderTest::create_products();
        $this->product_1 = $products[0];
        $this->product_2 = $products[1];
        $this->product_3 = $products[2];

        // create a FavoritesList for user_1
        $this->favorites_list_1 = FavoritesList::create([
            'user_id' => $this->user_1->id,
            'name' => $this->user_1->name . "'s Favorites",
            'views_count'=>0,
            'likes_count'=>0
            //ratio 0
        ]);
        Favorite::create([
            'favorites_list_id' => $this->favorites_list_1->id, 
            'product_id' => $this->product_1->id
        ]);

        // create a FavoritesList for user_2
        $this->favorites_list_2 =FavoritesList::create([
            'user_id' => $this->user_2->id,
            'name' => $this->user_2->name . "'s Favorites",
            'views_count'=>3,
            'likes_count'=>3
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
            'likes_count'=>2
            //ratio : -2

        ]);
        Favorite::create([
            'favorites_list_id' =>$this->favorites_list_3->id, 
            'product_id' => $this->product_3->id
        ]);

        //create a FavroitesList for user_4
        $user_4 = User::create(['id'=>6,'name'=>"askf", 'email'=>"ansdf@email.com",'password'=>"234"]);
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
        $request->assertOk();
        $this->assertEquals($old_likes_count+1 , $this->favorites_list_1->likes_count);
        $request->assertJson(['likes_count' => $old_likes_count+1, "action" =>"added"]);
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
        $request->assertJson(['likes_count' => $old_likes_count-1, "action" =>"removed"]);
    }

    public function test_like_favorites_list_does_not_exist():void
    {
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $request = $this->postJson('api/favorites_lists/324324/like',[],$headers);
        $request->assertBadRequest();
        $request->assertJson(['message' => "favorites list not found."]);
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
        $this->assertEquals($old_views_count+1, $this->favorites_list_1->views_count);
        $request->assertJson(['views_count'=>$old_views_count+1, "action"=>"viewed"]);
    }

    public function test_view_favorites_list_again():void
    {
        $old_views_count = $this->favorites_list_1->views_count;
        $headers = ['Authorization' => "Bearer ".$this->token_2];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertOk();
        $this->assertEquals($old_views_count+1, $this->favorites_list_1->views_count);
        $request->assertJson(['views_count'=>$old_views_count+1, "action"=>"viewed"]);
    }

    public function test_view_favorites_list_unauthorized():void
    {
        $old_views_count = $this->favorites_list_1->views_count;
        $headers = [];
        $request = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $this->favorites_list_1->refresh();
        $request->assertOk();
        $this->assertEquals($old_views_count+1, $this->favorites_list_1->views_count);
        $request->assertJson(['views_count'=>$old_views_count+1, "action"=>"viewed"]);
    }

    //admin and super-admin views don't count
    public function test_view_favorites_list_by_admin_and_super_admin():void
    {
        //create an admin account
        $admin = User::create(['id'=>8,"name"=>"admin","email"=>"admin@gmail.com", "password"=>"123321"]);
        $token_admin = $admin->createToken("admin_token", ["admin"], Carbon::now()->addDays(1))->plainTextToken;
        //create super admin account
        $super_admin = User::create(['id'=>7,"name"=>"super_admin","email"=>"super_admin@gmail.com", "password"=>"123321"]);
        $token_super_admin = $super_admin->createToken("super_admin_token", ["super-admin"], Carbon::now()->addDays(1))->plainTextToken;
    

        //view by admin
        $headers = ["Authorization" => "Bearer " . $token_admin];
        $old_views_count = $this->favorites_list_1->views_count;
        
        $admin_view = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $admin_view->assertOk();
        $this->favorites_list_1->refresh();

        $admin_view->assertJson(["views_count"=>0 ,"action"=>"viewed"]);
        $this->assertEquals($old_views_count,$this->favorites_list_1->views_count);

        //view by super-admin
        $headers = ["Authorization" => "Bearer " . $token_super_admin];
        $super_admin_view = $this->postJson('api/favorites_lists/'.$this->favorites_list_1->id.'/view',[],$headers);
        $super_admin_view->assertOk();
        $this->favorites_list_1->refresh();

        $super_admin_view->assertJson(["views_count"=>0 ,"action"=>"viewed"]);
        $this->assertEquals($old_views_count,$this->favorites_list_1->views_count);
    }

    // retrieveFavoritesList method 


    // listFavoritesLists method 

    //initially sorted by most_popular (ratio num_views/num_likes)
    public function test_list_favorites_lists():void
    {
        $request = $this->getJson("api/favorites_lists");
        $request->assertOk();
        $request->assertJson([
            "favorites_lists" =>[
                [
                    "id" =>$this->favorites_list_2->id,
                ],
                [
                    'id' =>$this->favorites_list_3->id,
                ],
                [
                    "id" =>$this->favorites_list_1->id
                ]
            ],
            "count"=>3,
            "total_count"=>3
        ]);
        $request->assertJsonMissing([
            "id"=>$this->favorites_list_4->id
        ]);
    }

    // public function test_list_favorites_lists_limited():void
    // {
    //     $request = $this->getJson("api/favorites_lists?page=1&limit=1");
    //     $request->assertOk();
    //     $request->assertJson([
    //         "favorites_lists" =>[],
    //         "count"=>1,
    //         "total_count"=>3
    //     ]);
    //     $request->assertJsonMissing([
    //         "id"=>$this->favorites_list_4->id
    //     ]);
    // }


    // public function test_list_favorites_lists_sort_by_most_viewed():void
    // {
    //     $request= $this->getJson("api/favorites_lists?sort-by=views_count-DESC");
    //     $request->assertOk();
    //     $request->assertJson([
    //         "favorites_lists"=>[
    //             [
    //                 'id' =>$this->favorites_list_3->id,
    //             ],
    //             [
    //                 "id" =>$this->favorites_list_2->id,
    //             ],
    //             [
    //                 "id" =>$this->favorites_list_1->id
    //             ]
    //         ],
    //         "count"=>3,
    //         "total_count"=>3
    //     ]);
    // }

    // public function test_list_favorites_lists_sort_by_most_liked():void
    // {
    //     $request= $this->getJson("api/favorites_lists?sort-by=likes_count-DESC");
    //     $request->assertOk();
    //     $request->assertJson([
    //         "favorites_lists"=>[
    //             [
    //                 'id' =>$this->favorites_list_2->id,
    //             ],
    //             [
    //                 "id" =>$this->favorites_list_3->id,
    //             ],
    //             [
    //                 "id" =>$this->favorites_list_1->id
    //             ]
    //         ],
    //         "count"=>3,
    //         "total_count"=>3
    //     ]);
    // }

    // public function test_list_favorites_lists_search():void
    // {
    //     $request= $this->getJson("api/favorites_lists?q=abc");
    //     $request->assertOk();
    //     $request->assertJson([
    //         "favorites_lists"=>[
    //             [
    //                 'id' =>$this->favorites_list_2->id,
    //             ],
    //             [
    //                 "id" =>$this->favorites_list_1->id
    //             ]
    //         ],
    //         "count"=>2,
    //         "total_count"=>3
    //     ]);
    // }

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