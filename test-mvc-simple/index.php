<?php
/**
 * ============================================================================
 * SIMPLE MVC - Standalone PHP MVC Framework
 * ============================================================================
 * 
 * Một MVC framework siêu đơn giản, không phụ thuộc Laravel
 * Dùng để test serve và hiểu cơ bản về MVC pattern
 * 
 * Truy cập: http://yourdomain.com/test-mvc-simple/
 * 
 * Routes có sẵn:
 * - /test-mvc-simple/              → Home page
 * - /test-mvc-simple/about         → About page
 * - /test-mvc-simple/user/123      → User profile
 * - /test-mvc-simple/api/test      → API test
 */

// ============================================================================
// CORE: Router Class
// ============================================================================
class Router {
    private $routes = [];
    
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }
    
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $uri = str_replace($basePath, '', $uri);
        $uri = '/' . trim($uri, '/');
        
        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            return call_user_func($this->routes[$method][$uri]);
        }
        
        // Try pattern matching
        foreach ($this->routes[$method] ?? [] as $route => $callback) {
            $pattern = $this->convertToPattern($route);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                return call_user_func_array($callback, $matches);
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        View::render('404');
    }
    
    private function convertToPattern($route) {
        // Convert /user/:id to regex pattern
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }
}

// ============================================================================
// CORE: View Class
// ============================================================================
class View {
    public static function render($view, $data = []) {
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = __DIR__ . '/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "<h1>View not found: $view</h1>";
        }
        
        // Get buffered content
        $content = ob_get_clean();
        
        // Include layout
        include __DIR__ . '/views/layout.php';
    }
    
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================================================
// CORE: Controller Base Class
// ============================================================================
class Controller {
    protected function view($view, $data = []) {
        View::render($view, $data);
    }
    
    protected function json($data, $status = 200) {
        View::json($data, $status);
    }
}

// ============================================================================
// CONTROLLERS
// ============================================================================

class HomeController extends Controller {
    public function index() {
        $this->view('home', [
            'title' => 'Home Page',
            'message' => 'Welcome to Simple MVC!',
        ]);
    }
    
    public function about() {
        $this->view('about', [
            'title' => 'About Page',
            'framework' => 'Simple MVC v1.0',
            'features' => [
                'Routing system',
                'Controller base class',
                'View rendering',
                'Layout support',
                'JSON API support',
            ],
        ]);
    }
}

class UserController extends Controller {
    public function profile($id) {
        // Simulate fetching user from database
        $users = [
            '1' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            '2' => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            '123' => ['id' => 123, 'name' => 'Test User', 'email' => 'test@example.com'],
        ];
        
        $user = $users[$id] ?? null;
        
        if ($user) {
            $this->view('user/profile', [
                'title' => 'User Profile',
                'user' => $user,
            ]);
        } else {
            http_response_code(404);
            $this->view('404');
        }
    }
}

class ApiController extends Controller {
    public function test() {
        $this->json([
            'status' => 'success',
            'message' => 'API is working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => [
                'framework' => 'Simple MVC',
                'version' => '1.0',
                'php_version' => PHP_VERSION,
            ],
        ]);
    }
}

// ============================================================================
// ROUTES DEFINITION
// ============================================================================

$router = new Router();

// Home routes
$router->get('/', function() {
    $controller = new HomeController();
    $controller->index();
});

$router->get('/about', function() {
    $controller = new HomeController();
    $controller->about();
});

// User routes
$router->get('/user/:id', function($id) {
    $controller = new UserController();
    $controller->profile($id);
});

// API routes
$router->get('/api/test', function() {
    $controller = new ApiController();
    $controller->test();
});

// ============================================================================
// DISPATCH REQUEST
// ============================================================================

$router->dispatch();
?>

