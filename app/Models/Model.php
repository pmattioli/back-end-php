<?php
namespace App\Models;

abstract class Model extends \Illuminate\Database\Eloquent\Model {
    
    protected $columns = [];
    
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        foreach ($this->columns as $convention => $actual) {
            if (array_key_exists($actual, $attributes)) {
                $attributes[$convention] = $attributes[$actual];
                unset($attributes[$actual]);
            }
        }
        return $attributes;
    }
    
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->columns)) {
            $key = $this->columns[$key];
        }
        return parent::getAttributeValue($key);
    }
    
    public function setAttribute($key, $value)
    {
        if (array_key_exists($key, $this->columns)) {
            $key = $this->columns[$key];
        }
        return parent::setAttribute($key, $value);
    }
    
}

