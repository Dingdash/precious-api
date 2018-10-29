<?php

namespace Chatter\Models;

class Categories extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'category_id';
    protected $table = 'categories';
    public function output()
    {
        $output = [];
        $output['description'] = $this->category_desc;
        $output['name'] = $this->category_name;
        $output['category_uri'] = '/categories/'.$this->category_id;
        $output['created_at'] = $this->created_at;
        $output['picture'] = $this->category_pict;
        $output['category_id'] = $this->category_id;
        return $output;
    }
}

