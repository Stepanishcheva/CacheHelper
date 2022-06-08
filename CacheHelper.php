<?php
class CacheHelper extends CacheProvider
{
    protected PDO $db;
    protected string $tableName = 'cache';

    public function __construct(array $settings)
    {
        $config = [
            'host'    => 'localhost',
            'port'    => 5432,
            'user'    => null,
            'pass'    => null,
            'dbname'  => null,
            'table'   => 'cache'
        ];

        foreach (array_keys($config) as $key) {
            if (isset($settings[$key])) {
                $config[$key] = $settings[$key];
            }
        }
        $this->assertSettingFields($config);
        $this->connect($config);
        $this->collectGarbage();
    }


    protected function connect(array $config): void
    {
        $host = 'pgsql' .
            ':host='   . $config['host'] .
            ';port='   . $config['port'] .
            ';dbname=' . $config['dbname'];

        $user = $config['user'];
        $pass = $config['pass'];
        $this->tableName = $config['table'];
        try {
            $this->db = new PDO($host, $user, $pass);
            $this->rebuild();
        } catch (PDOException $e) {
            throw new CacheException($e->getMessage());
        }
    }


    protected function rebuild(): bool
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS $this->tableName 
                (
                    cache_key varchar(40) NOT NULL,
                    cache_value varchar,
                    PRIMARY KEY (cache_key)
                );";
            $this->db->query($sql);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    protected function doGet(string $key): array
    {
        $sql = 'SELECT * 
                FROM ' . $this->tableName . '
                WHERE cache_key = :cache_key';

        $query = $this->db->prepare($sql);
        $query->bindValue(':cache_key', $key);
        $query->execute();
        $resultData = $query->fetch($this->db::FETCH_ASSOC);

        if (empty($resultData['cache_value'])) {
            return [];
        }
        return unserialize($resultData['cache_value']);
    }

    protected function doSet(string $key, $value, int $ttl, int $timestamp): bool
    {
        $cacheData = $this->get($key);

        $sql = 'INSERT INTO ' . $this->tableName . '
                (cache_key, cache_value) 
                VALUES (:cache_key, :cache_value)';

        if (!empty($cacheData)) {
            $sql = 'UPDATE ' . $this->tableName . ' 
                    SET cache_value = :cache_value 
                    WHERE cache_key = :cache_key';
        }

        $query = $this->db->prepare($sql);
        $data = [
            'cache_key'   => $key,
            'cache_value' => serialize(
                [
                    'timestamp' => $timestamp,
                    'ttl' => $ttl,
                    'value' => $value,
                ]),
        ];
        return $query->execute($data);
    }

    protected function doDelete(string $key): bool
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' 
                WHERE cache_key = ?';
        $query = $this->db->prepare($sql);
        return $query->execute([$key]);
    }

    protected function doClear(): bool
    {
        $sql = 'TRUNCATE TABLE ' . $this->tableName;
        $query = $this->db->prepare($sql);
        return $query->execute();
    }

    protected function doHas(string $key): bool
    {
        $sql = 'SELECT COUNT(*) 
                FROM ' . $this->tableName . '
                WHERE cache_key = :cache_key';
        $query = $this->db->prepare($sql);
        $query->bindValue(':cache_key', $key);
        $query->execute();
        $count = $query->fetchColumn();
        return $count > 0;
    }


    protected function getAll(): array
    {
        $list = [];
        $sql = 'SELECT * 
                FROM ' . $this->tableName;
        $query = $this->db->prepare($sql);
        $query->execute();
        $results = $query->fetchAll($this->db::FETCH_ASSOC);

        foreach ($results as $row) {
            $key   = $row['cache_key'];
            $value = $row['cache_value'];

            $list[$key] = unserialize($value);
        }
        return $list;
    }

    /**
     * Сборщик мусора в бд для кэша
     * @return array
     */
    public function collectGarbage(): array
    {
        $list = [];
        $data = $this->getAll();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $ttl = (int)$value['ttl'];
                $time = (int)$value['timestamp'];

                if ($this->isExpired($ttl, $time)) {
                    $this->delete($key);
                    $list[] = $key;
                }
            }
        }
        return $list;
    }


}