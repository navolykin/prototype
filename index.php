<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', true);

require_once 'Services/Http/Request.php';

use Services\Http\Request;

$request = Request::fromGlobals();

echo '<pre>';

print_r($_SERVER);


echo $request->method() . '<br>';

echo $request->method . '<br>';

print_r($request->get(['test', 'method'], 'default'));
echo '<br>';

echo $request->isMethod('POST') . '<br>';

echo $request->has(['method', 'test']) . '<br>';

echo $request->ip() . '<br>';

echo $request->protocol() . '<br>';

echo $request->inMethods(['head', 'post']) . '<br>';

echo $request->method(['put', 'post']) . '<br>';

echo $request->isCustomMethod() . '<br>';

echo $request->getProtocolVersion() . '<br>';

echo $request->isGetMethod() . '<br>';

print_r($request->getHeaders());

echo $request->hasHeader('accept') . '<br>';

print_r($request->getHeader('accept')) . '<br>';

echo $request->getHeaderLine('accept') . '<br>';

echo $request->getHost() . '<br>';
echo $request->getUserAgent() . '<br>';
print_r($request->getAcceptTypes()) . '<br>';
echo $request->hasAcceptType('application/xml') . '<br>';

echo $request->isAjax() . '<br>';

print_r($request->getCookies()) . '<br>';

echo '</pre>';
