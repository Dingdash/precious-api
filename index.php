<?php

require 'vendor/autoload.php';
include 'bootstrap.php';


use Chatter\Models\Message;
use Chatter\Models\Userdata;
use Chatter\Models\Categories;
use Chatter\Models\Products;
use Chatter\Models\VariantModel;
use Chatter\Models\VariantSpecModel;
use Chatter\Models\Wishlist;
use Chatter\Models\Shipper;
use Chatter\Middleware\Logging as ChatterLogging;
use Chatter\Middleware\Authentication as ChatterAuth;
use Chatter\Middleware\FileFilter;
use Chatter\Middleware\Filemove;
use Chatter\Middleware\ImageRemoveExif;


$app = new \Slim\App();
//$app->add(new ChatterAuth());
$Auth = new ChatterAuth();
$app->add(new ChatterLogging());
$app->group('/v1' , function() use ($app) {
    $app->group('/messages', function () use ($app) {
        $this->map(['GET'], '', function ($request, $response, $args) {
            $_message = new Message();
            $messages = $_message->all();
            $payload = [];
            foreach($messages as $_msg) {
                $payload[$_msg->id] = $_msg->output();
            }

            return $response->withStatus(200)->withJson($payload);
        })->setName('get_messages');
    });
});
$app ->group ('/products',function()use($app){
        $this->map(['GET'],'/all',function($request,$response,$args){
            
            $_products = new Products();
            $_variant = new VariantModel();
            $_variantspec = new VariantSpecModel();
            
            $data = $_products->all()->toArray();
            
            for($i = 0; $i<count($data); $i++)
            {
                $data1 = $_variant->where('Product_ID','=',$data[$i]['Product_ID'])->get()->toArray();
                for($j = 0; $j<count($data1);$j++)
                {
                    $data2 = $_variantspec->where("Specification_ID",'=',$data1[$j]['Specification_ID'])->get()->toArray();
                    
                    $data1[$j]['Specifications']=$data2;
           
                    
                }
                $data[$i]['variant']=$data1;
            }
            if(count($data)>0)
            {
                $payload['data'] = $data;
                $payload['exit'] = false;
                $payload['message'] = 'data retrieved';
                return $response->withStatus(200)->withJson($payload);
            }else
            {
                $payload['exit'] = true;
                $payload['message'] = 'data not retrieved';
                return $response->withStatus(400)->withJson($payload);
            }

        });
    $this->map(['GET'],'/all/{id}',function($request,$response,$args){
        //get by category
            $_products = new Products();
            $page=$request->getQueryParam('page',null);
            $perpage=10;
            $total = $_products->where("Category_ID",'=',$args['id'])->count();
            if($page!=null)
            {
                //$products = $_products->where("Category_ID",'=',$args['id'])->get();
                $products = $_products->where("Category_ID",'=',$args['id'])->skip($page*$perpage)->take(10)->get();
                
            }else
            {
                $products = $_products->where("Category_ID",'=',$args['id'])->get();
            }
            for ($i = 0; $i<count($products);$i++)
            {   
                $payload[$i]['id']=$products[$i]['Product_ID'];
                $payload[$i]['name'] = $products[$i]['Product_name'];   
            }
            $var['data'] = $payload;
            $var['total'] = $total;
            $var['exit'] = false;
            return $response->withStatus(200)->withJson($var);
    });
    
    $this->map(['GET'],'/{id}',function($request,$response,$args){
        //single product
        $_products = new Products();
        $_variant = new VariantModel();
        $_variantspec = new VariantSpecModel();
        $products = $_products->where('Product_ID','=',$args['id'])->get()->first();
        $variants = $_variant->where('Product_ID','=',$args['id'])->get()->toArray();
        for($i=0; $i<count($variants);$i++)
        {
            //print_r($variants[$i]['Specification_ID']);
            $variants[$i]['Specifications'] = $_variantspec->where('Specification_ID','=',$variants[$i]['Specification_ID'])->get()->toArray();

        }
        $products['variant'] = $variants;
        $payload['data'] = $products;
        $payload['exit'] = false;
        return $response->withStatus(200)->withJson($payload);
    });
    $this->map(['GET'],'/{id}/variant/{ids}',function($request,$response,$args){
        //single product
        $_products = new Products();
        $_variant = new VariantModel();
        $_variantspec = new VariantSpecModel();
        $products = $_products->where('Product_ID','=',$args['id'])->get()->first();
        $variants = $_variant->where('Product_ID','=',$args['id'])->where('Specification_ID','=',$args['ids'])->get()->first();
        // for($i=0; $i<count($variants);$i++)
        // {
        //     //print_r($variants[$i]['Specification_ID']);
        //     $variants[$i]['Specifications'] = $_variantspec->where('Specification_ID','=',$variants[$i]['Specification_ID'])->get()->toArray();

        // }
        $products['variant'] = $variants;
        $products['price'] = $variants['Specification_price'];
        $payload['data'] = $products;
        $payload['exit'] = false;
        return $response->withStatus(200)->withJson($payload);
    });
   


   
    
    
});
$app->group('/categories',function()use($app){
    // all category
    $this->map(['GET'],'/all',function($request,$response,$args){
        $_categories = new Categories();
        $categories = $_categories->all();
        
        $payload = [];
        foreach($categories as $_cat){
            array_push($payload,$_cat->output());
        }
        
        return $response->withStatus(200)->withJson($payload);
    });
    //single category
    $this->map(['GET'],'/{id}',function($request,$response,$args){
        $_categories = Categories::find($args['id']);
          if(!$_categories)
          {
            $data['message'] = "no data found";
            $data['exit'] = false;
            return $response->withStatus(400)->withJson($data);
          }
   
        return $response->withStatus(200)->withJson($_categories);
    });
});
$app->group('/shipper',function()use($app){
    $this->map(['GET'],'/all',function($request,$response,$args){
            $_shippers = new Shipper();
            $shippers = $_shippers->all()->toArray();
            print_r($shippers);
    });
});
$app->group('/user',function()use($app){
    $this->map(['GET'],'/all',function($request,$response,$args){
        $_user = new Userdata();
        $user = $_user->all();
        $payload = [];
       
        foreach($user as $_users){
            $payload[$_users->id] = $_users->output();
        }
        return $response->withStatus(200)->withJson($payload);

    });
   
   
    $this->map(['POST'],'',function($request,$response,$args){
                //LOGIN
        $username = $request->getParsedBodyParam('username','');
        $password = $request->getParsedBodyParam('password','');
        $_user = new Userdata();
        $user = $_user->where('username',$username,'and')->where('password',$password)->take(1)->get();
        foreach($user as $s)
        {
            $payload=$s->output();
        }
        $data['data']=$payload;
        $data['message']="data retrieved";
        $data['exit'] = false;
        return $response->withStatus(200)->withJson($data);
       // $loggedin = User::where('username',$username)->take(1)->get();
    })->setName('getuserdata');
    $this->map(['POST'],'/edituser/firstname',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->first_name = $param['first_name'];
        //$userUpdate->last_name = $param['last_name'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
        
    });
    $this->map(['POST'],'/edituser/lastname',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        //$userUpdate->first_name = $param['first_name'];
        $userUpdate->last_name = $param['last_name'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
        
    });
    $this->map(['POST'],'/edituser/password',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->password = $param['password'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age,
                    'gender'=>$userUpdate->gender
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
    });
    $this->map(['POST'],'/edituser/address',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->address = $param['address'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age,
                    'gender'=>$userUpdate->gender
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = false;
            return $response->withStatus(400)->withJson($data);
        }
    });
    $this->map(['POST'],'/edituser/city',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->city = $param['city'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age,
                    'gender'=>$userUpdate->gender
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
    });
    $this->map(['POST'],'/edituser/postcode',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->post_code = $param['post_code'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age,
                    'gender'=>$userUpdate->gender
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
    });
    $this->map(['POST'],'/edituser/telp',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->telp = $param['telp'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age,
                    'gender'=>$userUpdate->gender
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
    });
    $this->map(['POST'],'/edituser/gender',function($request,$response,$args){
        $user = new Userdata();
        $param= $request->getParsedBody();
                    
        $userUpdate = $user->where('username','=','peter')->get()[0];
        $userUpdate->gender = $param['gender'];
        if($userUpdate)
        {
            $userUpdate->save();
            // success
            $payload = ['user_id' => $userUpdate->id,
                    'user_uri' => '/user/' . $userUpdate->id,
                    'first_name'   => $userUpdate->first_name,
                    'last_name' =>$userUpdate->last_name,
                    'age' => $userUpdate->age,
                    'gender'=>$userUpdate->gender
                    ];
            $data['data'] = $payload;
            
            $data['message'] = "update success";
            $data['exit'] = false;
            return $response->withStatus(200)->withJson($data);
        }else
        {
            $data['message'] = "update failed";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
    });
    
    $this->map(['POST'],'/createuser',function($request,$response,$args){
            // Email, Nama depan, nama blakang, umur, alamat, no telp, gender, negara, provinsi ,kota
        $user = new Userdata();
        $getemail = $user->where('email','=',$param['email'])->get();
        if($getemail == NULL)
        {
            $param= $request->getParsedBody();
            $user = new Userdata();   
            $user->username = $param['username'];
            $user->first_name = 'first_name';
            $user->last_name = 'last_name';
            $user->address = $param['address'];
            $user->city = $param['city'];
            $user->email = $param['email'];
            $user->post_code = $param['post_code'];
            $user->gender = $param['gender'];
            $user->telp = $param['telp'];
            $user->age = $param['age'];
            $user->password = $param['password'];
            $apiKey = $user->generateAPIKey();
            $user->apikey = $apiKey;
            $user->save();
            if($user->id)
            { $data['message'] = "registration successful";
              $data['exit'] = false;
                return $response->withStatus(201)->withJson($data);
            }
        }else
        {
            $data['message'] = "email has been used please use another email";
            $data['exit'] = true;
            return $response->withStatus(400)->withJson($data);
        }
    });
 
    $this->map(['GET'],'/profile',function ($request,$response,$args){
        //http://localhost/precious/user/profile?username=Peter
        $username = $request->getQueryParam('username');
        $_user = new Userdata();
        $user = $_user->where('username',$username)->take(1)->get();
        foreach($user as $s)
        {
            $payload = $s->output();
        }
        $data['data']=$payload;
        $data['message']="data retrieved";
        $data['exit'] = false;
        return $response->withStatus(200)->withJson($data);
    });

    $this->map(['GET'],'/wishlist',function($request,$response,$args){
        //all wishlist
        $query = Wishlist::select('*')
        ->leftJoin('Products', function($leftJoin)use($Product_ID)
        {
            $leftJoin->on('wishlist.Product_ID', '=', 'Products.Product_ID');
        })->get();
        return $response->withStatus(201)->withJson($query);
    });
    $this->map(['GET'],'/wishlist/{id}/{userid}',function($request,$response,$args){
        $_wishlist = new Wishlist();
        $wishlist = $_wishlist->where('Product_ID','=',$args['id'])->where('User_ID','=',$args['userid'])->get()->count();
        
        if($wishlist>0)
        {
            $payload['message'] = "You have added this product to wishlist";
            return $response->withStatus(200)->withJson($payload);
        }else
        {
            $_wishlist = new Wishlist();
            $_wishlist->Product_ID = $args['id'];
            $_wishlist->User_ID= $args['userid'];
            $_wishlist->save();
            $payload['message'] = "Added to wishlist";
            return $response->withStatus(200)->withJson($payload);

            
        }
    });
    $this->map(['GET'],'/wishlist/r/{id}/{userid}',function($request,$response,$args){
        $_wishlist = new Wishlist();
        $wishlist = $_wishlist->where('Product_ID','=',$args['id'])->where('User_ID','=',$args['userid'])->delete();
        $payload['message'] = "removed from wishlist";
        return $response->withStatus(200)->withJson($payload);
    });
});

$app->group('/search',function()use($app){
    

});
    



$filter = new FileFilter();
$removeExif = new ImageRemoveExif();
$move =  new Filemove();
$app->post('/messages', function($request, $response, $args){
    $_message = $request->getParsedBodyParam('message', '');
    $imagepath = '';
   
    $message = new Message();
    
    $message->body= $_message;
    $message->user_id = -1;
    $message->image_url = $imagepath;
    $message->save();
    if ($message->id) {
        $payload = ['message_id' => $message->id,
                    'message_uri' => '/messages/' . $message->id,
                    'image_url'   => $message->image_url
                    ];
        return $response->withStatus(201)->withJson($payload);
    } else {
        return $response->withStatus(400);
    }
})->add($filter)->add($removeExif)->add($move);

$app->delete('/messages/{message_id}', function ($request, $response, $args) {
    $message = Message::find($args['message_id']);
    $message->delete();
    if ($message->exists) {
        return $response->withStatus(400);
    } else {
        //204 not exist
        return $response->withStatus(204)->withJson($payload);
    }
});

// Run app
$app->run();

