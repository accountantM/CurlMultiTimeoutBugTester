# CurlMultiTimeoutBugTester
a php class just for testing the curl multi timeout out strange behaviour

use it like this

$curlTester = new CurlMultiTimeoutBugTester();
$report = $curlTester->test("https://www.example.com", 5, 1000);    
echo $report;
exit;

I made this repository to reference it in a stackoverflow.com question because the posts there has limited length
