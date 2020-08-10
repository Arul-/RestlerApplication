<?php


namespace App;


use Luracast\Restler\Contracts\TypedResponseInterface;
use Luracast\Restler\Data\Returns;
use ReflectionClass;

class PaginatedResponse implements TypedResponseInterface
{
    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function responds(string  ...$types): Returns
    {
        return Returns::__set_state([
            'type' => 'object',
            'properties' => [
                'current_page' => Returns::__set_state(['type' => 'int']),
                'data' => empty($types)
                    ? Returns::__set_state(['type' => 'object', 'scalar' => false])
                    : Returns::fromClass(new ReflectionClass($types[0])),
                'first_page_url' => Returns::__set_state(['type' => 'string']),
                'from' => Returns::__set_state(['type' => 'int']),
                'last_page' => Returns::__set_state(['type' => 'int']),
                'last_page_url' => Returns::__set_state(['type' => 'string']),
                'next_page_url' => Returns::__set_state(['type' => 'string']),
                'path' => Returns::__set_state(['type' => 'string']),
                'per_page' => Returns::__set_state(['type' => 'int']),
                'prev_page_url' => Returns::__set_state(['type' => 'string']),
                'to' => Returns::__set_state(['type' => 'int']),
                'total' => Returns::__set_state(['type' => 'int']),
            ]
        ]);
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
