<?php
    
    set_time_limit(0);
    
    $thandle = $_REQUEST['twitterhandle'];
    $responsetype = $_REQUEST['responsetype'];
    
    $tagnames = array();
    $tagcounts = array();
    $dtweets = array();
    $batch_count = 5;
    $total_tweets;
    $homepage;
    
    total_tweets($thandle);
    
    $max_cycles = 2;
    if($total_tweets < 5){
        $max_cycles = floor($total_tweets/$batch_count); 
    }
    
    for($i=0;$i<$max_cycles+1;$i++){
        build_input($i+1);
        process_tweets();
    }
        
    $n = count($tagnames);
    $tags = array();
    for($i=0;$i<$n;$i++){
        $tags[$i] = array("tag" => $tagnames[$i], "weight" => $tagcounts[$i]);
    }    
    usort($tags,"sort_tags");
    $result = array_slice($tags,0,8);
    $suggestions = array($homepage);
    
    switch($responsetype){
        case "json":
            $final_result = array("twitterhandle" => $thandle, "tags" => $result, "suggestions" => $suggestions);
            echo json_encode($final_result);
            break;
        case "html":
            echo "<html>
                    <head>
                      <script type='text/javascript' src='https://www.google.com/jsapi'></script>
                      <script type='text/javascript'>
                        google.load('visualization', '1', {packages:['corechart']});
                        google.setOnLoadCallback(drawChart);
                        function drawChart() {
                          var data = new google.visualization.DataTable();
                          data.addColumn('string', 'Tags');
                          data.addColumn('number', 'Weight');
                          data.addRows([";
                          foreach($result as $mydata){
                            $mytag = $mydata['tag'];
                            $myweight = $mydata['weight'];
                            echo "['$mytag',$myweight],";
                          }
                            
                          echo "]);
                  
                          var options = {
                            width: 900, height: 600,
                            title: 'Topics vs. Weights',
                            hAxis: {title: 'Tags', titleTextStyle: {color: 'red'}}
                          };
                  
                          var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
                          chart.draw(data, options);
                        }
                      </script>
                    </head>
                    <body>
                      <div id='chart_div'></div>
                    </body>
                  </html>";
            break;
    }
    
    function total_tweets($thandle){
        global $total_tweets,$homepage;
        $resource = "http://api.twitter.com/1/users/show.json"
                                    ."?screen_name=$thandle";
        $user_details = file_get_contents($resource);
        $duser_details = json_decode($user_details,true);
        $total_tweets = $duser_details['statuses_count'];
        $homepage = $duser_details['url'];
    }
    
    function build_input($page){
        global $thandle,$dtweets,$batch_count;
        $resource = "http://api.twitter.com/1/statuses/user_timeline.json"
                                    ."?screen_name=$thandle"
                                    ."&include_entities=true"
                                    ."&trim_user=true"
                                    ."&count=$batch_count"
                                    ."&page=$page";
        $tweets = file_get_contents($resource);
        $dtweets = json_decode($tweets,true);
    }
    
    function process_tweets(){
        global $dtweets;
        foreach($dtweets as $tweet){
            if(!empty($tweet['entities']['media'])){
                process_urls($tweet['entities']['media']);
            } elseif(!empty($tweet['entities']['urls'])){
                process_urls($tweet['entities']['urls']);
            }elseif(!empty($tweet['entities']['hashtags'])){
                //echo "Calling process hashtag";
                process_hashtags($tweet['entities']['hashtags']);
            }
        }
    }
    
    function process_urls($entity){
        global $tagnames,$tagcounts;
        $url = $entity[0]['expanded_url'];
        $apikey = "a2ab1a98f1174ecac062aa1a8f9d4f62a86c85c4";
        $sitedata = file_get_contents("http://access.alchemyapi.com/calls/url/URLGetRankedKeywords"
                                    ."?url=$url"
                                    ."&apikey=$apikey"
                                    ."&outputMode=json");
        $temp = json_decode($sitedata,true);
        $concepts = $temp['keywords'];
        foreach($concepts as $concept){
            $iconcept = str_replace(" ","",$concept['text']);
            if($key = array_search($iconcept,$tagnames)){
                $tagcounts[$key] += $concept['relevance'];
            }else{
            array_push($tagnames,$iconcept);
            array_push($tagcounts,1);
            }
        }
    }
    
    function process_hashtags($entity){
        //echo "Entered process hashtag";
        global $tagnames,$tagcounts;
        $hashtag = $entity[0]['text'];
        if($key = array_search($hashtag,$tagnames)){
            $tagcounts[$key] += 8;
        }else{
            array_push($tagnames,$hashtag);
            array_push($tagcounts,1);
        }
    }
    
    function sort_tags($a,$b){
        if($a['weight']<=$b['weight']){
            return 1;
        } else{
            return -1;
        }
    }
    
?>