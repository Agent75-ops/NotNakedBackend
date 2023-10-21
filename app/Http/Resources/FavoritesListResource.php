<?php

namespace App\Http\Resources;

use App\Http\Controllers\ProductController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoritesListResource extends JsonResource
{
    public static $currentUser;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function __construct($resource, $currentUser=null){
        parent::__construct($resource);
        if ($currentUser instanceof User)
            self::$currentUser = $currentUser;
    }
  
    public function toArray(Request $request): array
    {
        return( 
            parent::toArray($request) + 
            ["is_liked_by_current_user" => $this->IsLikedByCurrentUser(self::$currentUser)]      
        );
    }
    public static function collection_custom($resource, $user)
    {   
        self::$currentUser = $user;
        return self::collection($resource);
    }

}
