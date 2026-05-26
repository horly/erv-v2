<?php

namespace App\Http\Middleware;

use App\Support\AppBranding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectApplicationTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        $content = (string) $response->getContent();

        if (
            (! str_contains(strtolower($contentType), 'text/html') && ! str_contains($content, '</head>'))
            || str_contains($content, 'id="applicationThemeColors"')
        ) {
            return $response;
        }

        $style = '<style id="applicationThemeColors">'.AppBranding::themeCss().'</style>';

        $response->setContent(str_replace('</head>', $style.'</head>', $content));

        return $response;
    }
}
