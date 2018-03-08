<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model{
    protected $fillable = ['name', 'category_id'];

    public function references(){
        return $this -> hasMany('\App\Reference');
    }

    public function marks(){
        return $this -> hasMany('\App\Mark');
    }

    public function links(){
        return $this -> hasMany('\App\Link');
    }

    public function glances(){
        return $this -> hasMany('\App\Glance');
    }

    public function tags(){
        return $this -> belongsToMany('\App\Tag');
    }
}