<?php
namespace Spark\MVC\Controller;

use Phalcon\Mvc\Controller;

class BaseController extends Controller
{
    public function json($arr)
    {
        return $this->response->setJsonContent($arr);
    }
}
