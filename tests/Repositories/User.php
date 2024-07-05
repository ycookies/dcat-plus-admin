<?php

namespace Tests\Repositories;

use Dcatplus\Admin\Repositories\EloquentRepository;
use Tests\Models\User as Model;

class User extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
