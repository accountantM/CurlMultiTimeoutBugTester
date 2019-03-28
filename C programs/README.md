I believe curl-multi interface sometimes report 28 error (timeout) while it shouldn't when it is used to test many public proxies at the same time.

These are 2 c programs to test the same proxies servers:

 1- uses curl-multi interface on 1 thread.
 
 2- uses curl on many threads (1 operation per thread).

out of the 407 proxy servers in the programs there is about ~80 proxies will work with the 2 programs, however the curl-multi interface program will SOMETIMES report many timeouts errors and report only ~0-5 proxies are working


**HOW TO COMPILE THE PROGRAMS**

go to the curl-multi-tester program folder and write in the terminal

    gcc -o"curl-multi-tester" main.c proxies.c -lcurl
    
go to the curl-threads-tester program folder and write in the terminal

    gcc -o"curl-threads-tester" main.c proxies.c -lcurl -lpthread

**HOW TO USE THE PROGRAMS**

1- one test mode

go to the curl-multi-tester program folder and write in terminal

    ./curl-multi-tester
    
and go to the curl-threads-tester program folder and write in the terminal

    ./curl-threads-tester
    

2- cron job mode (run the programs every 2 minutes and log the results in the cCurlTestLogs.txt file)

open the crontab in terminal

    crontab -e
    
add the 2 cron jobs

   
    0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * * cd /path/to/threads-program && ./curl-threads-tester -noprintToConsole -writeSummeryToFile >>my-cron-logs.log 2>&1
    1,3,5,7,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,53,55,57,59 * * * * cd /path/to/multi-program && ./curl-multi-tester -noprintToConsole -writeSummeryToFile  >>my-cron-logs.log 2>&1


[**see the cron job tests result**](https://github.com/accountantM/CurlMultiTimeoutBugTester/blob/master/C%20programs/testResults.txt)















