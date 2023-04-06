<?php

namespace App\Classes;

use ZipArchive;

/**
 * A simple wrapper around ZipArchive for shared functionality.
 */
class CompressedFile
{
    /**
     * Absolute path to the compressed file
     *
     * @var string|null
     */
    private $path = NULL;

    /**
     * ZipArchive object representation of the file being operated on
     *
     * @var ZipArchive|null
     */
    private $zip = NULL;

    /**
     * Initialize our ZIP file
     *
     * @param string $file_path Absolute path to the file
     */
    public function __construct(string $file_path)
    {
        $this->path = $file_path;

        $this->zip = new ZipArchive();

        $this->zip->open($this->path);
    }

    /**
     * Get the number of files within the ZIP file
     */
    public function count(): int
    {
        return count($this->files());
    }

    /**
     * Extract an individual file by its relative path within the zip file
     *
     * @param  string    $file_to_extract Path of the file to be extracted, relative to zip file
     * @param  string    $to_path         Absolute path of where to extract the file
     * @return void      Doesn't return anything, allowing the caller to catch any exceptions that may occur
     * @throws Exception if output file could not be opened or written to
     */
    public function extractFile(string $file_to_extract, string $to_path): void
    {
        $zip_stream = $this->zip->getStream($file_to_extract);
        $fp = fopen($to_path, 'w');

        if (! is_resource($fp)) {
            throw new Exception("The output file could not be opened for writing.");
        }

        while (! feof($zip_stream)) {
            if (fwrite($fp, fread($zip_stream, 4096)) === FALSE) {
                throw new Exception("The output file was successfully opened, but encountered an error when writing to it.");
            }
        }

        fclose($fp);
        fclose($zip_stream);
    }

    /**
     * Get a list of files within the ZIP file
     *
     * @return array A list of files within the ZIP file
     */
    public function files(): array
    {
        $files = [];

        // Limit our loop to 1,000 to prevent zip bombs
        for ($i = 0; $i < $this->zip->numFiles && $i < 1_000; ++$i) {
            $stat = $this->zip->statIndex($i);

            // Ignore annoying macOS trash files
            if (substr($stat['name'], 0, 8) == '__MACOSX' || substr($stat['name'], 0, 9) == '.DS_STORE') {
                continue;
            }

            $files[] = $stat['name'];
        }

        return $files;
    }
}