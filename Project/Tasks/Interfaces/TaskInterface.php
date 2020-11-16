<?php
namespace Project\Tasks\Interfaces;

interface TaskInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * In seconds
     *
     * @return  int
     */
    public function getDuration():         int;

    public function getDutchDescription(): string;
}
