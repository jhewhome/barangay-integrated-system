<?php



class AuditController extends Controller

{

    private PDO $db;



    public function __construct()

    {

        $this->db = (new Database())->getConnection();

    }



    public function index(): void

    {

        $this->requireRole('admin');

        $perPage = 30;

        $page = (int) ($_GET['page'] ?? 1);

        if ($page < 1) {

            $page = 1;

        }



        $filters = [

            'action' => trim((string) ($_GET['action'] ?? '')),

            'date_from' => trim((string) ($_GET['date_from'] ?? '')),

            'date_to' => trim((string) ($_GET['date_to'] ?? '')),

        ];



        $total = AuditLog::countFiltered($this->db, $filters);

        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {

            $page = $totalPages;

        }

        $offset = ($page - 1) * $perPage;



        $logs = AuditLog::paginate($this->db, $perPage, $offset, $filters);

        $actions = AuditLog::distinctActions($this->db);



        $this->view('audit/index', [

            'logs' => $logs,

            'page' => $page,

            'perPage' => $perPage,

            'total' => $total,

            'totalPages' => $totalPages,

            'filters' => $filters,

            'actions' => $actions,

        ]);

    }

}


