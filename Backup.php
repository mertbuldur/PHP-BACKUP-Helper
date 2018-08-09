<?php
class Backup
{
    public  $db;
    public  $host;
    public  $dbname;
    public  $username;
    public  $password;
    public  $sqlfilename;
    public  $sqlTables = "*";

    public  $sitefilename;
    public  $siteSource = __DIR__;
    public  $blockList = [];

    public $deleteDay = 0;

    public function __construct($host,$dname,$username,$password)
    {
        $this->host = $host;
        $this->dbname = $dname;
        $this->username = $username;
        $this->password = $password;
        $this->db = new PDO("mysql:host=".$this->host.";dbname=".$this->dbname.";charset=utf8",$this->username,$this->password);
    }

    public function MysqlBackUp(){
        $data = "";
        if($this->sqlTables)
        {
            $tables = array();
            $result = $this->db->prepare('SHOW TABLES');
            $result->execute();
            while($row = $result->fetch(PDO::FETCH_NUM))
            {
                $tables[] = $row[0];
            }
        }
        else
        {
            $tables = is_array($this->sqlTables) ? $this->sqlTables : explode(',',$this->sqlTables );
        }
        foreach($tables as $table)
        {
            $resultcount = $this->db->prepare('SELECT count(*) FROM '.$table);
            $resultcount->execute();
            $num_fields = $resultcount->fetch(PDO::FETCH_NUM);
            $num_fields = $num_fields[0];


            $resultcount2 = $this->db->prepare('SELECT *  FROM '.$table);
            $resultcount2->execute();
            $num_fields2 = $resultcount2->columnCount();


            $result = $this->db->prepare('SELECT * FROM '.$table);
            $result->execute();
            //$data.= 'DROP TABLE '.$table.';';

            $result2 = $this->db->prepare('SHOW CREATE TABLE '.$table);
            $result2->execute();
            $row2 = $result2->fetch(PDO::FETCH_NUM);
            $data.= "\n\n".$row2[1].";\n\n";


            for ($i = 0; $i < $num_fields; $i++)
            {
                while($row = $result->fetch(PDO::FETCH_NUM))
                {
                    $data.= 'INSERT INTO '.$table.' VALUES(';
                    for($j=0; $j<$num_fields2; $j++)
                    {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n","\\n",$row[$j]);
                        if (isset($row[$j])) { $data.= '"'.$row[$j].'"' ; } else { $data.= '""'; }
                        if ($j<($num_fields2-1)) { $data.= ','; }
                    }
                    $data.= ");\n";
                }
            }


            $data.="\n\n\n";

        }

        $this->writeUTF8filename($this->sqlfilename,$data);
    }

    public function writeUTF8filename($filenamename,$content){
        $f=fopen($filenamename,"w+");
        //fwrite($f, pack("CCC",0xef,0xbb,0xbf));
        fwrite($f,$content);
        fclose($f);
    }

    static function strpos_arr($haystack, $needle) {
        if(!is_array($needle)) $needle = array($needle);
        foreach($needle as $what) {
            if(($pos = strpos($haystack, $what))!==false) return $pos;
        }
        return false;
    }

    static function blockList($haystack,$needle)
    {
        foreach($needle as $value) {
            if ($haystack == $value)
            {
                return true;
            }
        }
        return false;
    }

    public function SiteBackUp() {
        $returnArray = [];
        if (extension_loaded('zip')) {
            if (file_exists($this->siteSource)) {
                $zip = new ZipArchive();
                if ($zip->open($this->sitefilename, ZIPARCHIVE::CREATE)) {
                    $source = realpath($this->siteSource);
                    if (is_dir($source)) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($files as $file) {
                            if(!self::strpos_arr($file,$this->blockList) and !self::blockList($file,$this->blockList)) {
                                $file = realpath($file);

                                if (is_dir($file)) {
                                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                                } else if (is_file($file)) {
                                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                                }

                            }
                        }
                    } else if (is_file($source))
                    {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }
                $zip->close();
            }
            return $returnArray;
        }
        else
        {
            return false;
        }

    }

    public function start($type)
    {
        if(!file_exists("backup"))
        {
            mkdir("backup", 0755, true);
        }
        $this->sqlfilename = "backup/".date("Y-m-d")."_sql.sql";
        $this->sitefilename = "backup/".date("Y-m-d")."_site.zip";
        if($type == 0)
        {
            $this->MysqlBackUp();
        }
        elseif($type == 1)
        {
            $this->siteBackUp();
        }
        else
        {
            $this->MysqlBackUp();
            $this->SiteBackUp();
        }

        if($this->deleteDay != 0)
        {
            for($i = 1; $i <= $this->deleteDay; $i++)
            {
                $date = date("Y-m-d",strtotime("- $i Day",time()));
                if(file_exists("backup/".$date."_sql.sql"))
                {
                    unlink("backup/".$date."_sql.sql");
                }

                if(file_exists("backup/".$date."_site.sql"))
                {
                    unlink("backup/".$date."_sql.sql");
                }
            }
        }

    }
}