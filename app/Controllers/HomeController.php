<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;

final class HomeController
{
    #[Route('GET', '/health')]
    public function health(Request $request): Response
    {
        return Response::json(['status' => 'ok']);
    }
}
