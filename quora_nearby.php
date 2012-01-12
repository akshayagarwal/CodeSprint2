<?php

$input_handle = fopen("C:\Users\Akshay\Desktop\\nsip\cs\quora\input00.txt","r");
$output_handle = fopen("C:\Users\Akshay\Desktop\\nsip\cs\quora\output01.txt","w");
$topics = array();
$questions = array();
build_input();
solve_queries();

class Topic {
    private $tid;
    private $location;
    
    public function __construct($tid,$lat,$lon){
        $this->tid = $tid;
        $this->location = array($lat,$lon);
    }
    
    public function getTid(){
        return $this->tid;
    }
    
    public function getLat(){
        return $this->location[0];
    }
    
    public function getLon(){
        return $this->location[1];
    }
}

class Question {
    private $qid;
    //no. of topics
    private $ntopics;
    //topics list
    private $tlist;
    
    public function __construct($qid,$ntopics,$atopics){
        $this->qid = $qid;
        $this->ntopics = $ntopics;
        if($ntopics!=0){
            $this->tlist = $atopics;
        }
    }
    
    public function getQid(){
        return $this->qid;
    }
    
    public function getNtopics(){
        return $this->ntopics;
    }
    
    public function getTlist(){
        return $this->tlist;
    }
}

class Tresult {
    private $tid;
    private $distance;
    
    public function __construct($tid,$distance){
        $this->tid = $tid;
        $this->distance = $distance;
    }
    
    public function getTid(){
        return $this->tid;
    }
    
    public function getDistance(){
        return $this->distance;
    }
}

class Qresult {
    private $qid;
    private $distance;
    
    function __construct($qid,$distance){
        $this->qid = $qid;
        $this->distance = $distance;
    }
    
    public function getQid(){
        return $this->qid;
    }
    
    public function getDistance(){
        return $this->distance;
    }
}

function build_input(){
    global $t,$q,$n,$topics,$questions,$input_handle; 
    list($t,$q,$n) = fscanf($input_handle,"%d %d %d\n");
    for($i=0;$i<$t;$i++){
        list($tid,$lat,$lon) = fscanf($input_handle,"%d %f %f\n");
        array_push($topics,new Topic($tid,$lat,$lon));
    }
    for($i=0;$i<$q;$i++){
        $question_data = fgetcsv($input_handle,1500," ");
        array_push($questions,new Question($question_data[0],$question_data[1],array_slice($question_data,2)));
    }
}

function display_output($result){
    global $output_handle;
    fwrite($output_handle,$result);
}

function solve_queries(){
    global $input_handle;
    while($query = fscanf($input_handle,"%c %d %f %f")){
        list($query_type,$quantity,$lat,$lon) = $query;
        switch($query_type){
            case 't':
                $tresults = fetch_topics($lat,$lon,$quantity);
                $result = "";
                foreach($tresults as $tresult){
                    $result = $result . $tresult->getTid() . " ";
                }
                display_output($result."\n");
                break;
            case 'q':
                $qresults = fetch_questions($lat,$lon,$quantity);
                $result = "";
                foreach($qresults as $qresult){
                    $result = $result . $qresult->getQid() . " ";
                }
                display_output($result."\n");
                break;
        }
    }
}

//Returns distance between two points in km using haversine formula
function distance($lat1,$lon1,$lat2,$lon2){
    $r = 6371; //radius of earth in km
    $delta_lat = deg2rad($lat2-$lat1);
    $delta_lon = deg2rad($lon2-$lon1);
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $a = sin($delta_lat/2) * sin($delta_lat/2) +
         sin($delta_lon/2) * sin($delta_lon/2) * cos($lat1) * cos($lat2);
    $c = 2 * atan2(sqrt($a),sqrt(1-$a));
    $dist = $r * $c;
    return $dist;
}

function fetch_topics($lat,$lon,$quantity=0){
    global $topics;
    $tresults = array();
    foreach($topics as $topic){
        array_push($tresults,new Tresult($topic->getTid(),distance($lat,$lon,$topic->getLat(),$topic->getLon())));
    }
    usort($tresults,"tresults_sort");
    if($quantity!=0){
        $tresults = array_slice($tresults,0,$quantity);
    }
    return $tresults;
}

function fetch_questions($lat,$lon,$quantity){
    global $topics,$questions;
    $qresults = array();
    $tresults = fetch_topics($lat,$lon,0);
    foreach($questions as $question){
        if($question->getNtopics()!=0){
            $tlist = $question->getTlist();
            foreach($tresults as $tresult){
                if(in_array($tresult->getTid(),$tlist)){
                    array_push($qresults,new Qresult($question->getQid(),$tresult->getDistance()));
                    break;
                }
            }
        }
    }
    usort($qresults,"qresults_sort");
    return array_slice($qresults,0,$quantity);
}

function tresults_sort($a,$b){
    if($a->getDistance() < $b->getDistance()){
        return -1;
    } elseif ($a->getDistance() > $b->getDistance()){
        return 1;
    } //distance same
    else{
        if($a->getTid() < $b->getTid()){
            return 1;
        } else {
            return -1;
        }
    }
}

function qresults_sort($a,$b){
    if($a->getDistance() < $b->getDistance()){
        return -1;
    } elseif ($a->getDistance() > $b->getDistance()){
        return 1;
    } //distance same
    else{
        if($a->getQid() < $b->getQid()){
            return 1;
        } else {
            return -1;
        }
    }
}
?>