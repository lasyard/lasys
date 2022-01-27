<?php
// This file exists only to make intelliphese happy and not used at all.
abstract class Imagick
{
    public abstract function __construct($p);
    public abstract function setImageCompressionQuality($p);
    public abstract function setOption($p1, $p2);
    public abstract function stripImage();
    public abstract function writeImage($p);
}
