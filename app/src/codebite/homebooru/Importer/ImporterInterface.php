<?php
namespace \codebite\homebooru\Importer;

interface ImporterInterface
{
	function fetch($id);
	function fetchImage();
}
