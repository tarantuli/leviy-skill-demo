<?php
namespace Shared\Csv;

class CsvBlob
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  iterable  $rows
     * @param  bool      $includeHeaderRow
     *
     * @return  static
     */
    public static function fromIterable(iterable $rows, bool $includeHeaderRow = true): self
    {
        $blob = new self();
        $isFirstRow = true;

        foreach ($rows as $row) {
            if ($isFirstRow) {
                $isFirstRow = false;

                if ($includeHeaderRow) {
                    $blob->addRow(array_keys($row));
                }
            }

            $blob->addRow($row);
        }

        return $blob;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * The content blob, starts with the UTF-8 BOM as its content
     *
     * @var  string
     */
    private $content = "\xEF\xBB\xBF";

    /**
     * @var  string
     */
    private $lineEnding;

    /**
     * @var  string
     */
    private $separator;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $separator
     * @param  string  $lineEnding
     */
    public function __construct(string $separator = ';', string $lineEnding = CRLF)
    {
        $this->separator  = $separator;
        $this->lineEnding = $lineEnding;
    }

    /**
     * Returns the compiled blob
     *
     * @return  string
     */
    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * Adds a row to the blob
     *
     * @param  array  $row
     */
    public function addRow(array $row)
    {
        $this->content .= implode($this->separator, $row);
        $this->content .= CRLF;
    }
}
