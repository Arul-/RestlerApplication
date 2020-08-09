<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Review
 *
 * @property-read  int    $id
 * @property       string $name
 * @property       string $email
 * @property       string $message
 * @property-read  string $created_at {@type date}
 * @property-read  string $updated_at {@type date}
 *
 */
 class Review extends Model
{

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'reviews';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ //TODO: edit fillable
        'email',
        'message',
        'name'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];
}
