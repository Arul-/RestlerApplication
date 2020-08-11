<?php


namespace App\Http\Controllers;


use App\Review;
use Psr\Http\Message\ServerRequestInterface;
use Luracast\Restler\Data\PaginatedResponse;
use Luracast\Restler\Exceptions\HttpException;

/**
 * manage review resources
 */
class Reviews
{
    /**
     * @var string
     */
    private $path;

    /**
     * a listing review resources
     *
     * @param int $page {@min 1} page number
     * @param int $per_page {@min 1} number of items per page
     *
     * @return PaginatedResponse {@type Review}
     */
    public function index(int $page = 1, int $per_page = 15): PaginatedResponse
    {
        return new PaginatedResponse(Review::paginate($per_page, ['*'], 'page', $page)->setPath($this->path));
    }

    /**
     * get a review by id
     *
     * @param int $id
     * @return Review
     *
     * @throws HttpException 404 review not found
     */
    public function get(int $id): Review
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
     * @param int $id
     * @return Review
     * @throws HttpException 404 review not found
     */
    public function delete(int $id): Review
    {
        if (!$review = Review::find($id)) {
            throw new HttpException(404, 'review not found');
        }
        $review->delete();
        return $review;
    }

    public function __construct(ServerRequestInterface $request)
    {
        $this->path = $request->getUri()->getPath();
    }
}
