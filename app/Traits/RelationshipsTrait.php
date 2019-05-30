<?php

namespace App\Traits;

use ErrorException;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

trait RelationshipsTrait
{


    public function relationships($by = 'name') {//name|model
        $model = new static;

        $relationships = [];

        foreach((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
            if ($method->class != get_class($model) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__) {
                continue;
            }

            try {
                $return = $method->invoke($model);
                if ($return instanceof Relation) {
                    //dd((new ReflectionClass($return))->getShortName());
/*                    $relationships[$method->getName()] = [
                        'type' => (new ReflectionClass($return))->getShortName(),
                        'model' => (new ReflectionClass($return->getRelated()))->getName(),
                        'method'=>$method->getName()
                    ];*/
                    if((new ReflectionClass($return))->getShortName() == "HasMany")
                    {
                        if($method->getName() != "companyContacts")
                        {
                            $relationships[]= $by == 'name' ? $method->getName() : (new ReflectionClass($return->getRelated()))->getName();
                        }

                    }

                    /*array_push($relationships,$method->getName());*/
                }
            } catch(ErrorException $e) {}
        }

        return $relationships;
    }

}

