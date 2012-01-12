<?php

$orders = array();
$fraud_orders = array();
build_input();
usort($orders,"deal_sort");
check_fraud();
display_output($fraud_orders);

class Order{
    private $orderid;
    private $dealid;
    private $email;
    private $street;
    private $city;
    private $state;
    private $zip;
    private $card;
    
    public function __construct($orderid,$dealid,$email,$street,$city,$state,$zip,$card){
        $this->orderid = $orderid;
        $this->dealid = $dealid;
        $this->email = $email;
        $this->street = $street;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
        $this->card = $card;
    }
    
    public function getOrderid(){
        return $this->orderid;
    }
    
    public function getDealid(){
        return $this->dealid;
    }
    
    public function getEmail(){
        return $this->email;
    }
    
    public function getStreet(){
        return $this->street;
    }
    
    public function getCity(){
        return $this->city;
    }
    
    public function getState(){
        return $this->state;
    }
    
    public function getZip(){
        return $this->zip;
    }
    
    public function getCard(){
        return $this->card;
    }
}

function build_input(){
    global $orders;
    $handle = fopen("C:\Users\Akshay\Desktop\\nsip\cs\input00.txt","r");
    list($num_orders) = fscanf($handle,"%d\n");
    while($num_orders>0){
        $order_data = fgetcsv($handle,1000,",");
        array_push($orders,new Order($order_data[0],$order_data[1],filter_email(strtolower($order_data[2])),filter_street(strtolower($order_data[3])),strtolower($order_data[4]),filter_state(strtolower($order_data[5])),$order_data[6],$order_data[7]));
        $num_orders--;
    }
}

function display_output($result){
    $result = array_unique($result,SORT_NUMERIC);
    asort($result);
    $handle = fopen("C:\Users\Akshay\Desktop\\nsip\cs\output01.txt","w");
    fputcsv($handle,$result,",");
}

function filter_email($email){
    //get the username
    list($username,$domain) = explode("@",$email);
    $username = preg_replace("/\.|\+\w*/","",$username);
    return $username . "@" . $domain;
}

function filter_street($street){
    //replace "street" with "st." in order to treat both as same
    $long_forms = array("/street/","/road/");
    $short_forms = array("st.","rd.");
    $street = preg_replace($long_forms,$short_forms,$street);
    return $street;
}

function filter_state($state){
    //replace the long forms of states with their short forms to treat both as same
    $long_forms = array("/illinois/","/california/","/new\s?york/");
    $short_forms = array("il","ca","ny");
    $state = preg_replace($long_forms,$short_forms,$state);
    return $state;
}

function deal_sort($a,$b){
    $id1 = $a->getDealid();
    $id2 = $b->getDealid();
    if( $id1 < $id2){
        return -1;
    } elseif($id1 > $id2){
        return 1;
    } else{
      return 0;  
    }
}

function email_fraud($order1,$order2){
    if(strcmp($order1->getEmail(),$order2->getEmail()) == 0){
        return true;
    } else{
        return false;
    }
}

function address_fraud($order1,$order2){
    if(strcmp($order1->getStreet(),$order2->getStreet()) == 0
       && strcmp($order1->getCity(),$order2->getCity()) == 0
       && strcmp($order1->getState(),$order2->getState()) == 0
       && strcmp($order1->getZip(),$order2->getZip()) == 0){
        return true;
    } else {
        return false;
    }
}

function check_fraud(){
    global $orders;
    global $fraud_orders;
    $current_key = 0;
    $max_iterations = count($orders)-1;
    while($current_key < $max_iterations){
        $current_order = $orders[$current_key];
        $current_dealid = $current_order->getDealid();
        $next_key = $current_key+1;
        $next_order = $orders[$next_key];
        //while the dealid of the next order is same as the current deal id
        while($next_order->getDealid()==$current_dealid){
            if(email_fraud($current_order,$next_order) || address_fraud($current_order,$next_order)){
                array_push($fraud_orders,$current_order->getOrderid(),$next_order->getOrderid());
            }
            if(++$next_key<$max_iterations){
                $next_order = $orders[$next_key];
            }else{
                break;
            }
        }
        $current_key++;
    }   
}   
?>
