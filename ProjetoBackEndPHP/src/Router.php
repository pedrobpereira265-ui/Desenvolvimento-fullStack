<?php

namespace App;

use App\Exceptions\ConflictException;
use App\Exceptions\DatabaseException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Middleware\CorsMiddleware;
use Throwable;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, callable|array $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): self
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => '/' . trim($path, '/'),
            'handler' => $handler,
        ];
        return $this;
    }

    public function dispatch(): void
    {
        // 1. Trata CORS e requisições OPTIONS
        CorsMiddleware::handle();

        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        $normalizedUri = $this->normalizeUri($requestUri);

        // Parse do corpo JSON para métodos com payload
        $body = [];
        if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'], true)) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            } elseif (!empty($_POST)) {
                $body = $_POST;
            }
        }

        try {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $requestMethod) {
                    continue;
                }

                $pattern = $this->compilePattern($route['path']);
                if (preg_match($pattern, $normalizedUri, $matches)) {
                    // Remove chave 0 (casamento completo)
                    array_shift($matches);

                    // Converte valores estritamente em argumentos posicionais
                    $params = [];
                    foreach ($matches as $k => $v) {
                        if (is_int($k)) {
                            $params[] = ctype_digit((string)$v) ? (int)$v : $v;
                        }
                    }

                    $handler = $route['handler'];

                    if (is_array($handler)) {
                        [$controllerClass, $method] = $handler;
                        $controller = new $controllerClass();

                        // Se o método do controller aceitar body (POST, PUT, PATCH)
                        if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'], true)) {
                            $params[] = $body;
                        }

                        call_user_func_array([$controller, $method], array_values($params));
                    } else {
                        if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'], true)) {
                            $params[] = $body;
                        }
                        call_user_func_array($handler, array_values($params));
                    }

                    return;
                }
            }

            // Rota não encontrada
            http_response_code(404);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'message' => "Rota [{$requestMethod} {$normalizedUri}] não encontrada."
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (ValidationException $e) {
            http_response_code(400);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            $response = ['message' => $e->getMessage()];
            if (!empty($e->getErrors())) {
                $response['errors'] = $e->getErrors();
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (UnauthorizedException $e) {
            http_response_code(401);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (ForbiddenException $e) {
            http_response_code(403);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (NotFoundException $e) {
            http_response_code(404);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (ConflictException $e) {
            http_response_code(409);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (DatabaseException $e) {
            http_response_code(503);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            // O detalhe tecnico vai para o log do servidor; o cliente recebe apenas
            // a mensagem generica, exceto quando APP_DEBUG=true (ambiente local).
            error_log('[Router] ' . $e::class . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }

            $response = ['message' => 'Erro interno no servidor.'];
            if (self::isDebug()) {
                $response['error'] = $e->getMessage();
                $response['file']  = $e->getFile() . ':' . $e->getLine();
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Indica se a API deve expor detalhes tecnicos de erro (somente em desenvolvimento).
     * Habilite com a variavel de ambiente APP_DEBUG=true.
     */
    private static function isDebug(): bool
    {
        $flag = getenv('APP_DEBUG');
        return is_string($flag) && in_array(strtolower($flag), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Normaliza a URI para suportar pastas aninhadas no XAMPP (ex: /ProjetoBackEndPHP/api/...).
     */
    private function normalizeUri(string $uri): string
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Remove prefixos conhecidos caso acesse por pastas
        $prefixes = ['/ProjetoBackEndPHP/public', '/ProjetoBackEndPHP', '/public'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                $uri = substr($uri, strlen($prefix));
                break;
            }
        }

        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    /**
     * Converte padrões como /api/eventos/{id} ou /api/eventos/:id em regex.
     */
    private function compilePattern(string $routePath): string
    {
        $pattern = preg_replace('#\{([a-zA-Z0-9_]+):int\}#', '([0-9]+)', $routePath);
        $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $pattern);
        $pattern = preg_replace('#:([a-zA-Z0-9_]+)#', '([^/]+)', $pattern);

        return '#^' . $pattern . '$#';
    }
}
