<?php
$x = array();
$d = array();
$w = array();
$avg_input = array();
$mmarray = array();
$n;
$m;

$input_handle = fopen("C:\Users\Akshay\Desktop\\nsip\cs\quora2\input00.txt","r");
$output_handle = fopen("C:\Users\Akshay\Desktop\\nsip\cs\quora2\output01.txt","w");

build_input();
training();
solve_queries();

function build_input(){
    global $input_handle,$x,$d,$n,$m;
    list($n,$m) = fscanf($input_handle,"%d %d");
    for($i=0;$i<$n;$i++){
        $temp = array();
        $rowdata = fgetcsv($input_handle,0," ");
        array_push($d,$rowdata[1]);
        for($j=0;$j<$m;$j++){
            list($param_num,$param_val) = sscanf($rowdata[2+$j],"%d:%f");
            array_push($temp,$param_val);
        }
        array_push($temp,1);
        array_push($x,$temp);
    }
}

function display_output($result){
    global $output_handle;
    fwrite($output_handle,$result);
}

function training(){
    global $x,$d,$w,$n,$m,$avg_input;
    /*
    $x = array(array(-1,-1,-1),array(-1,1,-1),array(1,-1,-1),array(1,1,-1));
    $d = array(1,1,1,-1);
    $m = 2;
    */
    $c = 0.1;
    
    
    //initialise weight at small values
    for($i=0;$i<($m+1);$i++){
        array_push($w,0.0);
    }
    
    $k = 1;
    $P = $n;
    $p = 0;
    $e = 0;
    
    build_min_max();
    
    array_walk($x,"normalize_input");

    //train using each pair
    $more_iterations = true;
    while($more_iterations){
        $y = $x[$p];
        //echo "y = ";
        //print_r($y);
        $net = 0;
        for($i=0;$i<($m+1);$i++){
            $net += $y[$i]*$w[$i];
        }
        //echo "net = $net";
        $out = sgn($net);
        //echo "out = $out";
        //update weights
        $diff = $d[$p]-$out;
        //echo "diff = $diff";
        if($diff != 0){
            $correction = 0.5*$diff;
            for($i=0;$i<($m+1);$i++){
                $w[$i] = $w[$i]+$correction*$y[$i];
            }
        }
        //echo "updates weight";
        //print_r($w);
        //calculate error
        $e += 0.5*$diff*$diff;
        
        if(++$p < $P){
            $k++;
        } else{
            if($e == 0){
                $more_iterations = false;
            }else{
                $e = 0;
                $p = 0;
                continue;
            }
        }
        //echo "p = $p AND e = $e <br/>";
    }
}

function sgn($a){
    if($a<0){
        return -1;
    } else {
        return 1;
    }
}

function normalize_input(&$y,$key){
    //echo "Inside normalize input";
    global $m,$mmarray;
    $nmax = 1;
    $nmin = -1;
    for($i=0;$i<($m+1);$i++){
        //echo "inside normalize 1";
        if($mmarray[$i][0] != $mmarray[$i][1]){
            $step1 = $y[$i] - $mmarray[$i][0];
            $step2 = $nmax-$nmin;
            $step3 = $mmarray[$i][1]-$mmarray[$i][0];
            $step4 = $step1*$step2;
            $step5 = $step4/$step3;
            $step6 = $step5+$nmin;
            $y[$i] = $step6;
            //echo "<br/>step1 = $step1, step2 = $step2, step3 = $step3, step4 = $step4, step5 = $step5, step6 = $step6<br/>";
        } else{
            $y[$i]=$mmarray[$i][0];
        }
    }
}

function solve_queries(){
    global $x,$d,$w,$avg_input,$n,$m,$input_handle;
    //echo "<pre>";
    //print_r($w);
    list($total_queries) = fscanf($input_handle,"%d");
    while($total_queries > 0){
        $rowdata = fgetcsv($input_handle,0," ");
        //print_r($rowdata);
        $qid = $rowdata[0];
        $temp = array();
        for($j=0;$j<$m;$j++){
            list($param_num,$param_val) = sscanf($rowdata[1+$j],"%d:%f");
            array_push($temp,$param_val);
        }
        array_push($temp,1);
        //print_r($temp);
        normalize_input($temp,0);
        
        //calculate net
        $net = 0;
        for($i=0;$i<($m+1);$i++){
            $net += $temp[$i]*$w[$i];
        }
        $out = sgn($net);
        if($out>0){
            $out = "+".$out;
        }
        $result = $qid . " " . $out;
        display_output($result . "\n");
        $total_queries--;
    }
}

function build_min_max(){
    //echo "inside build min max";
    global $x,$d,$w,$avg_input,$n,$m,$mmarray;
    
    for($i=0;$i<($m+1);$i++){
        $mmtemp = array();    
        for($j=0;$j<$n;$j++){
            array_push($mmtemp,$x[$j][$i]);
        }
        $mmarray[$i] = array(min($mmtemp),max($mmtemp));
    }
}
?>