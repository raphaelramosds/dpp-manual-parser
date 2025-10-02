<?php

namespace DppManualParser;

use DppManualParser\interfaces\ContextInterface;
use Exception;
use InvalidArgumentException;

class JsonContext implements ContextInterface
{
    private array $data;

    public function __construct(string $file)
    {
        try {
            if (!file_exists($file)) {
                throw new InvalidArgumentException("File not found: $file");
            }
            $str = file_get_contents($file);
            $this->data = json_decode($str);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getFields()
    {
        return $this->data;
    }

    public function getLabels()
    {
        return [];
    }
}
