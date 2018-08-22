<?php

/**
 * Description of template
 *
 * @package view
 */
interface templateInterface
{
    public function import($fullTemplateName);

    public function assign();

    public function fetch();

    public function display();
}
?>