<?php 

class CsvFile {
    private $filename = '';
    private $delimiter = ',';
    public $limit = 100;

    private $map = [
        'ЛИД' =>'LEAD',
        'КОМПАНИЯ' =>'COMPANY',
        'ИНН' =>'INN',
        'ФИЛИАЛ' =>'BRANCH',
        'НАЗВАНИЕЗАДАЧИ' =>'SUBJECT',
        'ОПИСАНИЕ' =>'DESCRIPTION',
        'ОТВЕТСТВЕННЫЙ' =>'RESPONSIBLE_ID',
        'ПОСТАНОВЩИК' =>'AUTHOR_ID',
        'СОИСПОЛНИТЕЛИ' =>'ACCOMPLICES',
        'НАБЛЮДАТЕЛИ' =>'AUDITORS',
        'КРАЙНИЙСРОК' =>'DEADLINE',
        'НАЧАТЬЗАДАЧУС' =>'START_TIME',
        'ЗАВЕРШИТЬЗАДАЧУ' =>'END_TIME',
        'РАЗРЕШИТЬОТВЕТСТВЕННОМУМЕНЯТЬСРОКИЗАДАЧИ' =>'ALLOW_CHANGE_DEADLINE',
        'ПРОПУСТИТЬВЫХОДНЫЕИПРАЗДНИЧНЫЕДНИ' =>'MATCH_WORK_TIME',
        'ПРИНЯТЬРАБОТУПОСЛЕЗАВЕРШЕНИЯЗАДАЧИ' =>'TASK_CONTROL',
        'СРОКИОПРЕДЕЛЯЮТСЯСРОКАМИПОДЗАДАЧ' =>'SE_PARAMETER_1',
        'АВТОМАТИЧЕСКИЗАВЕРШАТЬЗАДАЧУПРИЗАВЕРШЕНИИПОДЗАДАЧ' =>'SE_PARAMETER_2',
        'ПРОЕКТ' =>'SE_PROJECT',
        'ТЕГИ' =>'TAGS',
    ];

    public function getHeaders($headers){
        foreach ($headers as $i => $value) {
            $val = mb_strtoupper(preg_replace("`([^a-zA-Zа-яА-Я]+)`iu", "", $value));
            $val2 = mb_strtoupper(preg_replace("`([^a-zA-Zа-яА-Я]+)`iu", "",  iconv('windows-1251', 'utf-8', $value)));
            if(isset($this->map[$val])){
                $headers[$i] = $this->map[$val];
            }else if(isset($this->map[$val2])){
                $headers[$i] = $this->map[$val2];
            }else{
                $headers[$i] = $value;
            }
        }
        return $headers;
    }
    
    public function __construct($filename, $delimiter = ';'){
        $this->filename= $filename;
        $this->delimiter= $delimiter;
    }

    public function get(int $from)
    {
        $data = $this->read();
        if($data){
            $rows = array_slice($data, ($from*$this->limit) + 1, $this->limit);
            $headers = $this->getHeaders($data[0]);
            foreach ($rows as $i => $row) {
                foreach ($headers as $index => $value) {
                    $return[$i][$value] = $row[$index]; 
                }
            }
            return $return;
        }

        return [];
    }

    private function read()
    {
        $handle = fopen($this->filename, "r");
        $lineNumber = 1;
        while (($raw_string = fgets($handle)) !== false) {
            $row = str_getcsv(iconv('windows-1251', 'utf-8', $raw_string), $this->delimiter);
            $return[] = $row;
            $lineNumber++;
        }
        
        fclose($handle);
        return $return;
    }
}

