# CurlMultiTimeoutBugTester


a php class just for testing the curl multi timeout out strange behaviour

use it like this

```
$curlTester = new CurlMultiTimeoutBugTester();

$report = $curlTester->test("https://www.example.com", 5, 1000);    

echo $report;

exit;
```

I made this repository to reference it in a [stackoverflow.com question](https://stackoverflow.com/questions/54827396/in-curl-multi-interface-sometimes-error-connection-time-out-with-total-time-to) because the posts there has limited length



##### See issue https://github.com/curl/curl/issues/3602

