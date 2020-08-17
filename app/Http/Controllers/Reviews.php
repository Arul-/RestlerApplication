<?php

namespace App\Http\Controllers;

use App\Review;
use Luracast\Restler\Exceptions\HttpException;

/**
 * manage review resources
 */
class Reviews
{
    /**
     *  a listing of review resource.
     *
     * @return Review[]
     */
    public function index()
    {
        return Review::all();
    }

    /**
     * get a review by id
     *
     * @param string $id
     * @return Review
     *
     * @throws HttpException 404 review not found
     */
    public function get(string $id): Review
    {
        if (!$review = Review::find($id)) {
            throw new HttpException(404, 'review not found');
        }
        return $review;
    }

    /**
     * create new review
     *
     * @param Review $review
     * @return Review
     *
     * @status 201
     */
    public function post(Review $review): Review
    {
        $review->save();
        return $review;
    }

    /**
     * delete a review by id
     *
     * @param string $id
     * @return Review
     * @throws HttpException 404 review not found
     */
    public function delete(string $id): Review
    {
        if (!$review = Review::find($id)) {
            throw new HttpException(404, 'review not found');
        }
        $review->delete();
        return $review;
    }
}
