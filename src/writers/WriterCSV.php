<?php

namespace Src\Writers;

use Src\Contracts\Writer;

class WriterCSV implements Writer
{
    public function write(string $path, array $data): bool
    {
        $f = fopen($path, 'w');

        if ($f === false) {
            throw new \Exception('Failed to open file!');
        }
        
        foreach ($data as $fields) {
            fputcsv($f, $fields);
        }
        
        fclose($f);

        return true;
    }
}