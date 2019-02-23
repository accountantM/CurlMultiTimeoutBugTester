```php
/**
 * testing the curlmulti interface with many public proxies in the same time
 * cause cURL to time out for some requests with total_time info that is less that the CURLOPT_CONNECTTIMEOUT option
 * which is not happening if every request is done alone without the curl_multi.
 * 
 * The more curl handles attached to the curl multi, the more this error shows
 */
class CurlMultiTimeoutBugTester
{
    
    
    public function __construct()
    {
        
    }
    
    
    /**
     * 
     * @param string $url destination url that all proxies will try to connect to
     * @param number $connectTimeOut CURLOPT_CONNECTTIMEOUT
     * @param number $maxRequests maximum requests attached in the curlMulti object (if max is reached then flush 
     *     (execute the group and remove from the multi handle)) the more this variable and the less connectTimeout variable,
     *     the more this bug will appear
     * @param boolean $adjustMaxExecutiontime
     * @return string
     */
    public function test($url = "https://www.example.com/", $connectTimeOut = 5, $maxRequests = 1000, $totalProxies = "*", $adjustMaxExecutiontime = true)
    {
        $totalRequestsCount = count($this->proxies($totalProxies));
        
        $expectedTime = ceil((($totalRequestsCount > $maxRequests ? $totalRequestsCount : $maxRequests) / $maxRequests))  * $connectTimeOut;
        
        if( $expectedTime > 300 ) 
            throw new \InvalidArgumentException(' the test will take more than 5 minutes, if you want to wait, remove this exception');
            
        $report = "";
        $time1 = microtime(true);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // don't echo page
            //CURLOPT_VERBOSE =>true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeOut,
            CURLOPT_TIMEOUT => $connectTimeOut + 1
        );
        
        $mh = curl_multi_init();
        
        
        $handles = [];
        $x =  0;
        $errors = 0;
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        
        
        
        if($adjustMaxExecutiontime)ini_set("max_execution_time", ceil(($totalRequestsCount/$maxRequests) * $connectTimeOut) + 30 );
        
        $report =  "DateTime now in UTC " . $now->format("Y-m-d H:i:s") . "\n";
        $report .= "url: $url\n";
        $report .= "maxRequests: $maxRequests\n";
        $report .= "connectTimeOut: $connectTimeOut\n";
        $report .= "totalRequestsCount/totalProxies: $totalRequestsCount\n\n";
        foreach($this->proxies($totalProxies) as $proxy){
            $x++;
            $dynamicCH = "ch" . $x;
            $$dynamicCH = curl_init( $url );
            $options[CURLOPT_PROXY] = "{$proxy['ip']}:{$proxy['port']}"; //
            $options[CURLOPT_PROXYTYPE] = $proxy['type'] == "proxy4" ? CURLPROXY_SOCKS4  : CURLPROXY_HTTP;
            curl_setopt_array( $$dynamicCH, $options);
            curl_multi_add_handle($mh, $$dynamicCH);
            $handles[] = ["ch" => $$dynamicCH, "proxy" => "{$proxy['ip']}:{$proxy['port']}", "proxyType" => $proxy['type']];
            if($x % $maxRequests == 0 || $x >= $totalRequestsCount){
                $flushTime = microtime(true);
                //flush the group
                $this->executeMultiCurl($mh);
                foreach($handles as $handle){
                    $isError = false;
                    $info = curl_getinfo($handle["ch"]);
                    $err = curl_error($handle["ch"]);
                    if($connectTimeOut - $info["total_time"] > 1 && $err == "Connection time-out"){
                        $isError = true;
                        $errors++;
                    }
                    $report .= $handle["proxy"] . " ({$handle['proxyType']}) err:\"$err\", total_time:" . $info["total_time"] .  
                    ( $isError ? "  <=================   BUG " : "") . "\n";
                    curl_multi_remove_handle($mh, $handle["ch"]);
                }
                $handles = [];
                $mh = curl_multi_init();
                $report .= "======================================= Flushed(execute and remove all handles). time: " . (microtime(true) - $flushTime) . " seconds\n";
            }
        }
        $report .= "\n\nfunction took:" . (microtime(true) - $time1);
        $report .= "\n Bug reproduced: $errors times\n";
        
        return $report;
        
    }
    
    /**
     * 
     * 407 free public proxy list from free proxy lists websites 
     * (all https and socks4 and their anonymity level are elite and anonymous) checked on 2019-02-21
     * @return string[][]
     */
    public function proxies($limit = "*")
    {
      $proxies =  [
          ["ip" => "83.167.203.174", "port" => 44848, "type" => "https"],
          ["ip" => "83.219.1.80", "port" => 56004, "type" => "https"],
          ["ip" => "83.239.97.26", "port" => 50223, "type" => "https"],
          
          ["ip" => "84.241.19.214", "port" => 4145, "type" => "socks4"],
          ["ip" => "85.11.66.137", "port" => 55886, "type" => "https"],
          ["ip" => "85.52.217.114", "port" => 42544, "type" => "https"],
          ["ip" => "85.193.216.85", "port" => 59205, "type" => "https"],
          ["ip" => "85.193.229.6", "port" => 57712, "type" => "https"],
          ["ip" => "85.198.135.180", "port" => 54630, "type" => "https"],
          ["ip" => "86.38.39.21", "port" => 54703, "type" => "https"],
          ["ip" => "86.100.77.210", "port" => 53281, "type" => "https"],
          ["ip" => "87.244.182.181", "port" => 32598, "type" => "https"],
          ["ip" => "88.146.243.106", "port" => 35852, "type" => "https"],
          ["ip" => "88.150.135.10", "port" => 36624, "type" => "https"],
          ["ip" => "88.255.251.163", "port" => 8080, "type" => "https"],
          ["ip" => "89.67.144.213", "port" => 55605, "type" => "https"],
          ["ip" => "89.111.104.2", "port" => 41258, "type" => "https"],
          ["ip" => "89.189.189.115", "port" => 59249, "type" => "https"],
          ["ip" => "89.231.32.226", "port" => 55466, "type" => "socks4"],
          ["ip" => "91.139.1.158", "port" => 37504, "type" => "https"],
          ["ip" => "77.120.75.131", "port" => 35911, "type" => "https"],
          ["ip" => "77.222.20.246", "port" => 47888, "type" => "https"],
          ["ip" => "78.128.124.9", "port" => 47654, "type" => "https"],
          ["ip" => "78.132.136.40", "port" => 32431, "type" => "https"],
          ["ip" => "78.187.50.200", "port" => 30852, "type" => "https"],
          ["ip" => "79.78.184.12", "port" => 56834, "type" => "https"],
          ["ip" => "79.106.35.253", "port" => 8080, "type" => "https"],
          ["ip" => "79.106.41.15", "port" => 35876, "type" => "https"],
          ["ip" => "80.55.32.238", "port" => 59513, "type" => "https"],
          ["ip" => "80.71.112.51", "port" => 55243, "type" => "socks4"],
          ["ip" => "80.191.162.51", "port" => 44199, "type" => "socks4"],
          ["ip" => "80.211.154.106", "port" => 8080, "type" => "https"],
          ["ip" => "80.240.253.112", "port" => 50530, "type" => "https"],
          ["ip" => "80.249.229.64", "port" => 42186, "type" => "https"],
          ["ip" => "81.16.10.141", "port" => 59352, "type" => "https"],
          ["ip" => "81.163.62.221", "port" => 31299, "type" => "https"],
          ["ip" => "81.174.247.181", "port" => 8118, "type" => "https"],
          ["ip" => "81.199.32.214", "port" => 60172, "type" => "https"],
          ["ip" => "83.2.189.66", "port" => 38011, "type" => "https"],
          ["ip" => "83.13.63.38", "port" => 60860, "type" => "https"],
          ["ip" => "45.114.144.106", "port" => 23500, "type" => "https"],
          ["ip" => "46.20.93.83", "port" => 41258, "type" => "https"],
          ["ip" => "46.99.179.81", "port" => 48746, "type" => "https"],
          ["ip" => "46.151.108.6", "port" => 33727, "type" => "https"],
          ["ip" => "46.164.149.40", "port" => 36551, "type" => "https"],
          ["ip" => "46.228.199.70", "port" => 3128, "type" => "https"],
          ["ip" => "50.7.105.69", "port" => 48823, "type" => "socks4"],
          ["ip" => "50.7.106.228", "port" => 48823, "type" => "socks4"],
          ["ip" => "50.226.80.129", "port" => 42741, "type" => "socks4"],
          ["ip" => "51.38.71.101", "port" => 8080, "type" => "https"],
          ["ip" => "51.254.182.52", "port" => 61133, "type" => "https"],
          ["ip" => "58.68.228.234", "port" => 1234, "type" => "socks4"],
          ["ip" => "60.46.17.210", "port" => 3128, "type" => "https"],
          ["ip" => "62.74.230.146", "port" => 40018, "type" => "https"],
          ["ip" => "62.232.2.98", "port" => 53281, "type" => "https"],
          ["ip" => "65.173.110.238", "port" => 37252, "type" => "socks4"],
          ["ip" => "66.96.237.71", "port" => 36582, "type" => "https"],
          ["ip" => "67.226.65.195", "port" => 80, "type" => "https"],
          ["ip" => "68.183.17.69", "port" => 8080, "type" => "https"],
          ["ip" => "68.183.185.17", "port" => 8888, "type" => "https"],
          ["ip" => "37.19.94.86", "port" => 4145, "type" => "socks4"],
          ["ip" => "37.53.83.40", "port" => 50887, "type" => "socks4"],
          ["ip" => "41.78.172.20", "port" => 61169, "type" => "https"],
          ["ip" => "41.162.117.37", "port" => 8080, "type" => "https"],
          ["ip" => "41.170.11.226", "port" => 46093, "type" => "https"],
          ["ip" => "41.216.230.154", "port" => 56673, "type" => "socks4"],
          ["ip" => "41.217.216.45", "port" => 50951, "type" => "https"],
          ["ip" => "42.115.39.70", "port" => 49236, "type" => "https"],
          ["ip" => "43.229.27.175", "port" => 8080, "type" => "https"],
          ["ip" => "43.229.252.45", "port" => 53281, "type" => "https"],
          ["ip" => "45.63.51.89", "port" => 80, "type" => "https"],
          ["ip" => "45.64.122.210", "port" => 58181, "type" => "https"],
          ["ip" => "36.37.124.234", "port" => 36179, "type" => "https"],
          ["ip" => "36.37.163.220", "port" => 23500, "type" => "https"],
          ["ip" => "36.67.27.29", "port" => 56125, "type" => "https"],
          ["ip" => "36.67.27.189", "port" => 53323, "type" => "socks4"],
          ["ip" => "36.67.199.89", "port" => 47923, "type" => "https"],
          ["ip" => "36.89.81.129", "port" => 53732, "type" => "https"],
          ["ip" => "36.89.190.217", "port" => 45170, "type" => "https"],
          ["ip" => "36.89.227.50", "port" => 53281, "type" => "https"],
          ["ip" => "91.195.131.242", "port" => 4145, "type" => "socks4"],
          ["ip" => "91.200.125.248", "port" => 4145, "type" => "socks4"],
          ["ip" => "91.203.115.21", "port" => 45644, "type" => "socks4"],
          ["ip" => "91.214.240.19", "port" => 50481, "type" => "https"],
          ["ip" => "91.218.163.74", "port" => 50517, "type" => "socks4"],
          ["ip" => "91.228.32.70", "port" => 6969, "type" => "https"],
          ["ip" => "91.230.199.174", "port" => 60926, "type" => "https"],
          ["ip" => "91.235.7.216", "port" => 59067, "type" => "https"],
          ["ip" => "91.237.161.141", "port" => 45311, "type" => "https"],
          ["ip" => "92.39.56.15", "port" => 61917, "type" => "socks4"],
          ["ip" => "92.62.66.161", "port" => 4145, "type" => "socks4"],
          ["ip" => "92.115.102.133", "port" => 53281, "type" => "https"],
          ["ip" => "92.244.36.66", "port" => 60789, "type" => "socks4"],
          ["ip" => "92.244.36.68", "port" => 47150, "type" => "https"],
          ["ip" => "92.246.220.206", "port" => 40396, "type" => "https"],
          ["ip" => "93.87.51.138", "port" => 23500, "type" => "https"],
          ["ip" => "93.87.66.66", "port" => 61831, "type" => "https"],
          ["ip" => "93.116.57.4", "port" => 55674, "type" => "https"],
          ["ip" => "93.171.241.18", "port" => 45781, "type" => "https"],
          ["ip" => "93.177.150.112", "port" => 45186, "type" => "https"],
          ["ip" => "94.102.124.207", "port" => 35836, "type" => "https"],
          ["ip" => "94.179.130.34", "port" => 41293, "type" => "https"],
          ["ip" => "94.180.245.1", "port" => 61251, "type" => "https"],
          ["ip" => "94.190.190.254", "port" => 46269, "type" => "https"],
          ["ip" => "94.228.21.66", "port" => 55295, "type" => "https"],
          ["ip" => "94.240.46.195", "port" => 23500, "type" => "https"],
          ["ip" => "95.31.32.102", "port" => 34233, "type" => "https"],
          ["ip" => "95.38.211.185", "port" => 4145, "type" => "socks4"],
          ["ip" => "95.47.180.171", "port" => 53484, "type" => "https"],
          ["ip" => "95.66.140.121", "port" => 59078, "type" => "https"],
          ["ip" => "95.79.54.143", "port" => 80, "type" => "https"],
          ["ip" => "95.87.127.133", "port" => 50594, "type" => "https"],
          ["ip" => "95.140.27.135", "port" => 60275, "type" => "https"],
          ["ip" => "95.181.35.30", "port" => 40804, "type" => "https"],
          ["ip" => "96.9.69.230", "port" => 53281, "type" => "https"],
          ["ip" => "96.9.73.79", "port" => 61057, "type" => "https"],
          ["ip" => "96.9.91.27", "port" => 53523, "type" => "https"],
          ["ip" => "101.109.255.243", "port" => 50098, "type" => "https"],
          ["ip" => "101.255.124.202", "port" => 42608, "type" => "https"],
          ["ip" => "103.10.61.106", "port" => 59733, "type" => "socks4"],
          ["ip" => "103.12.246.65", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.15.140.138", "port" => 42584, "type" => "socks4"],
          ["ip" => "103.21.231.131", "port" => 53281, "type" => "https"],
          ["ip" => "103.28.243.11", "port" => 37120, "type" => "https"],
          ["ip" => "103.71.255.10", "port" => 51242, "type" => "https"],
          ["ip" => "103.73.86.67", "port" => 8080, "type" => "https"],
          ["ip" => "103.75.32.132", "port" => 53130, "type" => "https"],
          ["ip" => "103.75.34.121", "port" => 46210, "type" => "https"],
          ["ip" => "103.76.170.50", "port" => 60240, "type" => "https"],
          ["ip" => "103.76.188.85", "port" => 44827, "type" => "https"],
          ["ip" => "103.76.188.209", "port" => 45190, "type" => "https"],
          ["ip" => "103.77.23.202", "port" => 59311, "type" => "socks4"],
          ["ip" => "103.78.164.1", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.81.104.121", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.85.19.169", "port" => 39856, "type" => "socks4"],
          ["ip" => "103.91.120.65", "port" => 40540, "type" => "socks4"],
          ["ip" => "103.92.153.17", "port" => 57408, "type" => "socks4"],
          ["ip" => "103.94.7.254", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.94.169.114", "port" => 30372, "type" => "https"],
          ["ip" => "103.99.161.17", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.106.56.33", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.106.148.200", "port" => 53712, "type" => "https"],
          ["ip" => "103.106.216.21", "port" => 21913, "type" => "https"],
          ["ip" => "103.107.163.129", "port" => 51327, "type" => "socks4"],
          ["ip" => "103.108.62.14", "port" => 49271, "type" => "https"],
          ["ip" => "103.109.93.25", "port" => 44550, "type" => "socks4"],
          ["ip" => "103.110.184.163", "port" => 40186, "type" => "socks4"],
          ["ip" => "103.115.100.246", "port" => 47788, "type" => "https"],
          ["ip" => "103.192.169.186", "port" => 35865, "type" => "https"],
          ["ip" => "103.194.240.54", "port" => 53908, "type" => "socks4"],
          ["ip" => "103.205.27.46", "port" => 52311, "type" => "https"],
          ["ip" => "103.206.201.179", "port" => 37938, "type" => "https"],
          ["ip" => "103.211.8.125", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.221.254.2", "port" => 36778, "type" => "https"],
          ["ip" => "103.226.142.90", "port" => 47460, "type" => "socks4"],
          ["ip" => "103.226.227.218", "port" => 43010, "type" => "https"],
          ["ip" => "103.229.177.51", "port" => 45275, "type" => "https"],
          ["ip" => "103.237.174.82", "port" => 55326, "type" => "https"],
          ["ip" => "103.243.82.234", "port" => 59063, "type" => "socks4"],
          ["ip" => "103.248.31.244", "port" => 4145, "type" => "socks4"],
          ["ip" => "103.255.148.26", "port" => 35242, "type" => "https"],
          ["ip" => "105.255.223.2", "port" => 8080, "type" => "https"],
          ["ip" => "109.87.193.112", "port" => 33620, "type" => "https"],
          ["ip" => "109.225.41.174", "port" => 47234, "type" => "https"],
          ["ip" => "109.245.214.49", "port" => 42004, "type" => "https"],
          ["ip" => "110.36.218.126", "port" => 36651, "type" => "https"],
          ["ip" => "110.74.193.235", "port" => 53569, "type" => "https"],
          ["ip" => "110.74.196.9", "port" => 54848, "type" => "https"],
          ["ip" => "110.74.219.10", "port" => 30906, "type" => "https"],
          ["ip" => "1.10.186.48", "port" => 38104, "type" => "https"],
          ["ip" => "1.10.186.157", "port" => 51976, "type" => "https"],
          ["ip" => "1.10.187.6", "port" => 31097, "type" => "https"],
          ["ip" => "1.10.188.12", "port" => 35573, "type" => "https"],
          ["ip" => "1.10.188.205", "port" => 43580, "type" => "https"],
          ["ip" => "1.20.97.166", "port" => 32588, "type" => "https"],
          ["ip" => "1.20.99.176", "port" => 47005, "type" => "https"],
          ["ip" => "1.20.99.185", "port" => 44450, "type" => "https"],
          ["ip" => "1.20.100.42", "port" => 47962, "type" => "https"],
          ["ip" => "2.92.242.230", "port" => 40014, "type" => "https"],
          ["ip" => "3.8.44.17", "port" => 80, "type" => "https"],
          ["ip" => "3.8.121.95", "port" => 80, "type" => "https"],
          ["ip" => "5.19.142.82", "port" => 37589, "type" => "https"],
          ["ip" => "5.58.152.226", "port" => 60583, "type" => "https"],
          ["ip" => "5.128.32.12", "port" => 51959, "type" => "https"],
          ["ip" => "5.140.233.249", "port" => 33455, "type" => "https"],
          ["ip" => "5.157.115.235", "port" => 3128, "type" => "https"],
          ["ip" => "12.189.124.100", "port" => 44791, "type" => "https"],
          ["ip" => "13.233.72.134", "port" => 80, "type" => "https"],
          ["ip" => "18.219.3.105", "port" => 80, "type" => "https"],
          ["ip" => "18.220.245.122", "port" => 80, "type" => "https"],
          ["ip" => "18.228.217.208", "port" => 80, "type" => "https"],
          ["ip" => "24.172.34.114", "port" => 52143, "type" => "https"],
          ["ip" => "31.29.62.188", "port" => 4145, "type" => "socks4"],
          ["ip" => "31.42.254.24", "port" => 30912, "type" => "https"],
          ["ip" => "31.46.30.228", "port" => 51579, "type" => "https"],
          ["ip" => "31.145.137.166", "port" => 4145, "type" => "socks4"],
          ["ip" => "31.173.211.114", "port" => 43873, "type" => "https"],
          ["ip" => "31.200.228.169", "port" => 37293, "type" => "https"],
          ["ip" => "31.223.245.183", "port" => 4145, "type" => "socks4"],
          ["ip" => "34.244.2.233", "port" => 8123, "type" => "https"],
          ["ip" => "35.196.70.16", "port" => 80, "type" => "https"],
          ["ip" => "36.37.109.205", "port" => 808, "type" => "https"],
          ["ip" => "110.172.135.234", "port" => 53822, "type" => "https"],
          ["ip" => "110.235.198.3", "port" => 57660, "type" => "https"],
          ["ip" => "111.92.243.154", "port" => 37539, "type" => "https"],
          ["ip" => "113.11.47.242", "port" => 40071, "type" => "https"],
          ["ip" => "113.53.83.157", "port" => 35969, "type" => "https"],
          ["ip" => "113.108.242.36", "port" => 32655, "type" => "socks4"],
          ["ip" => "114.35.29.247", "port" => 42313, "type" => "socks4"],
          ["ip" => "115.127.39.66", "port" => 55474, "type" => "https"],
          ["ip" => "115.178.27.125", "port" => 48497, "type" => "https"],
          ["ip" => "116.212.152.128", "port" => 36041, "type" => "https"],
          ["ip" => "117.196.236.162", "port" => 43989, "type" => "https"],
          ["ip" => "118.172.201.109", "port" => 54347, "type" => "https"],
          ["ip" => "118.173.232.5", "port" => 55124, "type" => "https"],
          ["ip" => "118.173.232.43", "port" => 48204, "type" => "https"],
          ["ip" => "118.173.232.190", "port" => 13629, "type" => "socks4"],
          ["ip" => "118.174.234.129", "port" => 54431, "type" => "https"],
          ["ip" => "118.175.93.28", "port" => 61285, "type" => "https"],
          ["ip" => "118.175.93.164", "port" => 37694, "type" => "https"],
          ["ip" => "118.179.69.233", "port" => 44505, "type" => "https"],
          ["ip" => "118.179.84.106", "port" => 34236, "type" => "https"],
          ["ip" => "119.82.252.115", "port" => 49085, "type" => "https"],
          ["ip" => "119.82.253.155", "port" => 37436, "type" => "https"],
          ["ip" => "119.235.19.42", "port" => 38546, "type" => "https"],
          ["ip" => "119.252.175.38", "port" => 4550, "type" => "https"],
          ["ip" => "122.15.131.65", "port" => 57873, "type" => "https"],
          ["ip" => "122.102.41.82", "port" => 56067, "type" => "https"],
          ["ip" => "122.176.65.143", "port" => 42175, "type" => "https"],
          ["ip" => "124.41.211.213", "port" => 54986, "type" => "https"],
          ["ip" => "124.41.211.231", "port" => 49949, "type" => "https"],
          ["ip" => "124.41.213.203", "port" => 55187, "type" => "https"],
          ["ip" => "124.41.240.56", "port" => 50526, "type" => "https"],
          ["ip" => "125.27.251.208", "port" => 30753, "type" => "https"],
          ["ip" => "125.209.108.26", "port" => 53126, "type" => "https"],
          ["ip" => "128.40.76.198", "port" => 80, "type" => "https"],
          ["ip" => "128.201.97.156", "port" => 53281, "type" => "https"],
          ["ip" => "128.201.240.245", "port" => 4145, "type" => "socks4"],
          ["ip" => "130.193.112.146", "port" => 36923, "type" => "https"],
          ["ip" => "131.108.63.213", "port" => 57604, "type" => "https"],
          ["ip" => "131.255.4.49", "port" => 10770, "type" => "https"],
          ["ip" => "134.0.16.6", "port" => 47631, "type" => "https"],
          ["ip" => "138.36.108.30", "port" => 4145, "type" => "socks4"],
          ["ip" => "138.122.5.22", "port" => 61201, "type" => "https"],
          ["ip" => "138.186.122.105", "port" => 43602, "type" => "https"],
          ["ip" => "138.186.172.222", "port" => 33109, "type" => "socks4"],
          ["ip" => "139.178.127.128", "port" => 4145, "type" => "socks4"],
          ["ip" => "139.255.64.10", "port" => 41309, "type" => "https"],
          ["ip" => "141.101.236.49", "port" => 56000, "type" => "https"],
          ["ip" => "143.0.140.200", "port" => 3128, "type" => "https"],
          ["ip" => "149.255.243.78", "port" => 51372, "type" => "socks4"],
          ["ip" => "154.0.15.166", "port" => 49713, "type" => "https"],
          ["ip" => "154.41.2.154", "port" => 13538, "type" => "https"],
          ["ip" => "157.230.43.245", "port" => 8888, "type" => "https"],
          ["ip" => "159.192.97.83", "port" => 55938, "type" => "https"],
          ["ip" => "159.192.97.181", "port" => 23500, "type" => "https"],
          ["ip" => "163.53.150.230", "port" => 30195, "type" => "https"],
          ["ip" => "163.53.182.148", "port" => 39486, "type" => "https"],
          ["ip" => "165.90.211.69", "port" => 61692, "type" => "https"],
          ["ip" => "166.249.54.244", "port" => 55867, "type" => "https"],
          ["ip" => "167.99.79.1", "port" => 31330, "type" => "https"],
          ["ip" => "168.181.121.195", "port" => 51514, "type" => "https"],
          ["ip" => "168.205.84.55", "port" => 4145, "type" => "socks4"],
          ["ip" => "168.232.198.25", "port" => 32009, "type" => "https"],
          ["ip" => "169.255.67.244", "port" => 37804, "type" => "https"],
          ["ip" => "170.0.112.199", "port" => 50294, "type" => "socks4"],
          ["ip" => "170.0.112.226", "port" => 50294, "type" => "socks4"],
          ["ip" => "170.0.124.161", "port" => 4145, "type" => "socks4"],
          ["ip" => "170.79.121.38", "port" => 4145, "type" => "socks4"],
          ["ip" => "170.79.122.194", "port" => 4145, "type" => "socks4"],
          ["ip" => "170.79.123.26", "port" => 4145, "type" => "socks4"],
          ["ip" => "170.80.17.129", "port" => 40336, "type" => "https"],
          ["ip" => "170.80.52.33", "port" => 4145, "type" => "socks4"],
          ["ip" => "170.210.236.1", "port" => 34792, "type" => "https"],
          ["ip" => "170.231.140.150", "port" => 4145, "type" => "socks4"],
          ["ip" => "170.245.173.87", "port" => 4145, "type" => "socks4"],
          ["ip" => "172.96.224.179", "port" => 80, "type" => "https"],
          ["ip" => "175.100.5.52", "port" => 45940, "type" => "https"],
          ["ip" => "175.100.185.151", "port" => 53281, "type" => "https"],
          ["ip" => "176.98.75.120", "port" => 45259, "type" => "https"],
          ["ip" => "176.98.76.210", "port" => 45280, "type" => "https"],
          ["ip" => "176.103.45.27", "port" => 46994, "type" => "https"],
          ["ip" => "176.111.10.136", "port" => 46560, "type" => "https"],
          ["ip" => "176.119.18.92", "port" => 30996, "type" => "https"],
          ["ip" => "176.120.211.176", "port" => 58035, "type" => "https"],
          ["ip" => "176.196.238.234", "port" => 44648, "type" => "https"],
          ["ip" => "176.214.81.106", "port" => 41774, "type" => "https"],
          ["ip" => "176.221.112.82", "port" => 52094, "type" => "https"],
          ["ip" => "177.66.53.203", "port" => 43493, "type" => "https"],
          ["ip" => "177.66.255.232", "port" => 61798, "type" => "https"],
          ["ip" => "177.75.143.198", "port" => 43223, "type" => "https"],
          ["ip" => "177.220.171.54", "port" => 60729, "type" => "https"],
          ["ip" => "177.223.52.242", "port" => 42363, "type" => "https"],
          ["ip" => "178.32.59.233", "port" => 53281, "type" => "https"],
          ["ip" => "178.75.58.58", "port" => 41514, "type" => "https"],
          ["ip" => "178.92.9.210", "port" => 3128, "type" => "https"],
          ["ip" => "178.124.152.84", "port" => 52676, "type" => "https"],
          ["ip" => "178.128.58.122", "port" => 8080, "type" => "https"],
          ["ip" => "178.128.123.20", "port" => 8888, "type" => "https"],
          ["ip" => "178.150.242.139", "port" => 36086, "type" => "https"],
          ["ip" => "178.170.254.178", "port" => 46788, "type" => "https"],
          ["ip" => "178.217.168.77", "port" => 46062, "type" => "https"],
          ["ip" => "180.247.199.128", "port" => 8080, "type" => "https"],
          ["ip" => "181.112.57.34", "port" => 60148, "type" => "https"],
          ["ip" => "181.113.17.230", "port" => 59067, "type" => "https"],
          ["ip" => "181.113.135.254", "port" => 52058, "type" => "https"],
          ["ip" => "181.129.39.2", "port" => 29792, "type" => "https"],
          ["ip" => "181.191.241.179", "port" => 33670, "type" => "socks4"],
          ["ip" => "181.196.77.70", "port" => 53281, "type" => "https"],
          ["ip" => "181.196.147.98", "port" => 45882, "type" => "https"],
          ["ip" => "182.16.163.138", "port" => 8080, "type" => "https"],
          ["ip" => "182.52.51.15", "port" => 33680, "type" => "https"],
          ["ip" => "182.52.51.181", "port" => 3629, "type" => "socks4"],
          ["ip" => "182.53.206.40", "port" => 52214, "type" => "https"],
          ["ip" => "182.75.21.30", "port" => 46527, "type" => "https"],
          ["ip" => "182.253.201.10", "port" => 36776, "type" => "https"],
          ["ip" => "182.253.233.10", "port" => 8080, "type" => "https"],
          ["ip" => "183.81.157.178", "port" => 52555, "type" => "https"],
          ["ip" => "183.81.158.106", "port" => 4145, "type" => "socks4"],
          ["ip" => "185.59.244.161", "port" => 41258, "type" => "https"],
          ["ip" => "185.80.130.78", "port" => 3128, "type" => "https"],
          ["ip" => "185.112.249.187", "port" => 3128, "type" => "https"],
          ["ip" => "185.136.151.20", "port" => 58371, "type" => "https"],
          ["ip" => "185.162.57.251", "port" => 80, "type" => "https"],
          ["ip" => "185.194.24.187", "port" => 37854, "type" => "https"],
          ["ip" => "186.30.57.251", "port" => 48786, "type" => "https"],
          ["ip" => "186.38.39.22", "port" => 39524, "type" => "https"],
          ["ip" => "187.1.43.246", "port" => 53396, "type" => "https"],
          ["ip" => "187.87.241.25", "port" => 53386, "type" => "https"],
          ["ip" => "187.111.192.142", "port" => 30485, "type" => "https"],
          ["ip" => "188.121.103.187", "port" => 4145, "type" => "socks4"],
          ["ip" => "188.130.240.17", "port" => 52343, "type" => "https"],
          ["ip" => "188.186.182.125", "port" => 4145, "type" => "socks4"],
          ["ip" => "188.233.188.128", "port" => 31244, "type" => "https"],
          ["ip" => "189.127.35.51", "port" => 41615, "type" => "https"],
          ["ip" => "190.7.141.66", "port" => 47231, "type" => "https"],
          ["ip" => "190.53.46.14", "port" => 38618, "type" => "https"],
          ["ip" => "190.107.20.203", "port" => 4145, "type" => "socks4"],
          ["ip" => "190.128.135.130", "port" => 44915, "type" => "socks4"],
          ["ip" => "190.152.5.126", "port" => 53040, "type" => "https"],
          ["ip" => "190.193.8.28", "port" => 9999, "type" => "socks4"],
          ["ip" => "190.248.13.19", "port" => 50553, "type" => "https"],
          ["ip" => "190.248.136.18", "port" => 53963, "type" => "https"],
          ["ip" => "191.102.104.2", "port" => 80, "type" => "https"],
          ["ip" => "192.140.42.82", "port" => 39448, "type" => "https"],
          ["ip" => "193.59.101.12", "port" => 4145, "type" => "socks4"],
          ["ip" => "193.160.226.89", "port" => 53186, "type" => "https"],
          ["ip" => "194.67.37.90", "port" => 3128, "type" => "https"],
          ["ip" => "194.169.164.3", "port" => 57562, "type" => "https"],
          ["ip" => "195.39.71.252", "port" => 56935, "type" => "https"],
          ["ip" => "195.128.119.233", "port" => 4145, "type" => "socks4"],
          ["ip" => "195.177.75.106", "port" => 30297, "type" => "https"],
          ["ip" => "195.239.178.110", "port" => 40448, "type" => "https"],
          ["ip" => "196.18.215.37", "port" => 3128, "type" => "https"],
          ["ip" => "196.18.215.72", "port" => 3128, "type" => "https"],
          ["ip" => "196.18.215.75", "port" => 3128, "type" => "https"],
          ["ip" => "196.18.215.160", "port" => 3128, "type" => "https"],
          ["ip" => "196.18.215.230", "port" => 3128, "type" => "https"],
          ["ip" => "196.18.215.239", "port" => 3128, "type" => "https"],
          ["ip" => "196.32.109.73", "port" => 56930, "type" => "https"],
          ["ip" => "196.38.8.91", "port" => 8080, "type" => "https"],
          ["ip" => "196.201.206.129", "port" => 55625, "type" => "https"],
          ["ip" => "197.98.180.162", "port" => 61047, "type" => "https"],
          ["ip" => "197.232.55.224", "port" => 39385, "type" => "https"],
          ["ip" => "197.245.152.245", "port" => 38173, "type" => "https"],
          ["ip" => "197.248.224.242", "port" => 80, "type" => "https"],
          ["ip" => "198.144.108.36", "port" => 54321, "type" => "socks4"],
          ["ip" => "200.5.35.234", "port" => 4145, "type" => "socks4"],
          ["ip" => "200.7.205.194", "port" => 51910, "type" => "https"],
          ["ip" => "200.35.49.73", "port" => 45186, "type" => "https"],
          ["ip" => "200.71.123.185", "port" => 34343, "type" => "https"],
          ["ip" => "200.195.188.2", "port" => 32954, "type" => "https"],
          ["ip" => "200.255.122.170", "port" => 8080, "type" => "https"],
          ["ip" => "201.234.253.24", "port" => 59238, "type" => "https"],
          ["ip" => "202.3.72.6", "port" => 38725, "type" => "https"],
          ["ip" => "202.49.183.168", "port" => 30309, "type" => "https"],
          ["ip" => "202.57.39.126", "port" => 4145, "type" => "socks4"],
          ["ip" => "202.57.49.193", "port" => 4145, "type" => "socks4"],
          ["ip" => "202.57.55.242", "port" => 52634, "type" => "https"],
          ["ip" => "202.61.52.14", "port" => 60267, "type" => "https"],
          ["ip" => "202.84.79.11", "port" => 41247, "type" => "https"],
          ["ip" => "202.93.226.170", "port" => 4145, "type" => "socks4"],
          ["ip" => "202.178.125.99", "port" => 33853, "type" => "https"],
          ["ip" => "203.17.150.95", "port" => 38498, "type" => "https"],
          ["ip" => "203.76.147.26", "port" => 45684, "type" => "https"],
          ["ip" => "203.81.176.254", "port" => 49901, "type" => "https"],
          ["ip" => "203.129.195.183", "port" => 80, "type" => "https"],
          ["ip" => "203.142.69.242", "port" => 47619, "type" => "https"],
          ["ip" => "203.160.58.117", "port" => 4145, "type" => "socks4"],
          ["ip" => "203.174.15.234", "port" => 40604, "type" => "https"],
          ["ip" => "203.177.133.148", "port" => 44632, "type" => "https"],
          ["ip" => "206.189.137.227", "port" => 3128, "type" => "https"],
          ["ip" => "208.77.8.25", "port" => 54321, "type" => "socks4"],
          ["ip" => "208.98.185.89", "port" => 53630, "type" => "https"],
          ["ip" => "210.3.215.56", "port" => 8080, "type" => "https"],
          ["ip" => "210.16.120.108", "port" => 3128, "type" => "https"],
          ["ip" => "212.28.237.131", "port" => 59588, "type" => "https"],
          ["ip" => "212.72.159.174", "port" => 50323, "type" => "https"],
          ["ip" => "212.90.162.54", "port" => 50819, "type" => "https"],
          ["ip" => "212.154.200.6", "port" => 80, "type" => "https"],
          ["ip" => "213.6.67.86", "port" => 32586, "type" => "https"],
          ["ip" => "213.6.199.94", "port" => 41320, "type" => "https"],
          ["ip" => "213.23.110.196", "port" => 8080, "type" => "https"],
          ["ip" => "213.87.42.53", "port" => 40367, "type" => "https"],
          ["ip" => "213.174.6.207", "port" => 43935, "type" => "socks4"],
          ["ip" => "217.113.17.226", "port" => 50948, "type" => "https"],
          ["ip" => "221.120.163.242", "port" => 56969, "type" => "https"]
          
      ];
      
      if($limit == "*") return $proxies;
      $limit = (int)$limit;
      if((int)$limit > count($proxies)) throw new \InvalidArgumentException('limit is more than array count');
      return array_slice($proxies, 0, $limit);
      
    }
    
    protected function executeMultiCurl($mh)
    {
        $active = null;
        
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        while ($active && $mrc == CURLM_OK) {
             if (curl_multi_select($mh) == -1) {
                usleep(1);
             }
             
             do {
                $mrc = curl_multi_exec($mh, $active);
             } while ($mrc == CURLM_CALL_MULTI_PERFORM);
         }
    }
    
}
                        
```
