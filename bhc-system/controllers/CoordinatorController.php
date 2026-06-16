<?php

class CoordinatorController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->requireClinicStaff();

        $stations = Station::allActive($this->db);
        $counts = QueueTicket::stationCountsToday($this->db);

        $board = [];
        foreach ($stations as $s) {
            $id = (int) $s['id'];
            if ($id === 1) {
                continue;
            }
            $board[] = [
                'station' => $s,
                'nowServing' => QueueTicket::nowServing($this->db, $id),
                'waiting' => QueueTicket::waitingList($this->db, $id, 5),
                'skipped' => QueueTicket::skippedList($this->db, $id, 5),
                'counts' => $counts[$id] ?? ['waiting' => 0, 'serving' => 0],
            ];
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        $this->view('coordinator/index', [
            'board' => $board,
            'basePath' => $basePath,
        ]);
    }
}
