<?php namespace Phalcon\Queue;

use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Di\Injectable;
use Phalcon\Queue\Db\Model as JobModel;

require_once __DIR__.'/../tests/unit/DbTest.php';

/**
 * Tries to mimic Phalcon's Beanstalk Queue class for low-throughput queue needs
 *
 * <code>
 * $queue = new \Phalcon\Queue\Db();
 * </code>
 */
class Db extends Injectable
{

    /** @var \Phalcon\Db\Adapter\Pdo */
    protected $connection;

    /** Time to run (aka timeout) */
    const OPT_TTR      = 'ttr';
    /** How long to wait before this job becomes available */
    const OPT_DELAY    = 'delay';
    const OPT_PRIORITY = 'priority'; //TODO: test me under JobTest
    const OPT_TUBE     = 'tube';

    const OPTIONS = [
        self::OPT_DELAY,
        self::OPT_TTR,
        self::OPT_PRIORITY,
        self::OPT_TUBE
    ];

    /**
     * Queue manager constructor. By default, will look for a service called 'db'.
     * @todo implement some way to force a persistent db connection
     * @param string $di_service_key
     */
    public function __construct($di_service_key = 'db')
    {
        $this->connection = $this->getDI()->get($di_service_key);
    }

    /**
     * Inserts jobs into the queue
     *
     * @param mixed $data
     * @param array $options
     * @return string|bool
     */
    public function put($data, $options = [])
    {
        $payload = array_merge($options, ['body' => serialize($data)]);
        $job = new JobModel();
        $job->save($payload);
        return $job->id;
    }

    /**
     * Reserves a job in the queue
     *
     * @param mixed $timeout
     * @return bool|\Phalcon\Queue\Beanstalk\Job
     */
    public function reserve($timeout = null)
    {
    }

    /**
     * Change the active tube. By default the tube is "default"
     *
     * @param string $tube
     * @return bool|string
     */
    public function choose($tube)
    {
    }

    /**
     * Change the active tube. By default the tube is "default"
     *
     * @param string $tube
     * @return bool|string
     */
    public function watch($tube)
    {
    }

    /**
     * Get stats of the open tubes.
     *
     * @return bool|array
     */
    public function stats()
    {
        $result = JobModel::query()
            ->columns([
                'tube',
                'COUNT(*) AS total',
                'buried',
                'reserved',
                'delay <> 0 AS delayed'
            ])
            ->groupBy(['tube','buried','reserved','delayed'])
            ->orderBy('tube')
            ->execute();

        $structure = [
            'active'   => 0,
            'buried'   => 0,
            'delayed'  => 0,
            'reserved' => 0,
            'total'    => 0,
        ];
        $stats = ['all' => $structure];
        foreach ($result->toArray() as $entry) {
            $tube  = $entry['tube'];
            $total = $entry['total'];
            unset($entry['total'], $entry['tube']);

            if (!array_key_exists($tube, $stats)) {
                $stats[$tube] = $structure;
            }

            $entry = array_filter($entry); //leaves only the status with a value
            $status = $entry? array_keys($entry)[0] : 'active'; //if there's no key, it's the count for active jobs
            $stats[$tube][$status] += $total;
            $stats['all'][$status] += $total;
            $stats[$tube]['total'] += $total;
            $stats['all']['total'] += $total;
        }

        return $stats;
    }

    /**
     * Get stats of a tube.
     *
     * @param string $tube
     * @return bool|array
     */
    public function statsTube($tube = 'default')
    {
    }

    /**
     * Get list of a tubes.
     *
     * @return bool|array
     */
    public function listTubes()
    {
        $result = JobModel::query()
            ->distinct(true)
            ->columns('tube')
            ->orderBy('tube')
            ->execute()
            ->toArray();
        return array_column($result, 'tube');
    }

    /**
     * Inspect the next ready job.
     *
     * @return bool|\Phalcon\Queue\Beanstalk\Job
     */
    public function peekReady()
    {
    }

    /**
     * Return the next job in the list of buried jobs
     *
     * @return bool|\Phalcon\Queue\Beanstalk\Job
     */
    public function peekBuried()
    {
    }
}
