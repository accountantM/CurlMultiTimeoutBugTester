/***************************************************************************
 *
 *  Project    curl-threads-tester
 *
 ***************************************************************************/
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <sys/time.h>
#include <unistd.h>
#include <curl/curl.h>
#include <pthread.h>

#include "header.h"



extern struct one proxies[];


int writeSummeryToFile = 0;
int printToConsole = 1;

#define MAX_REQUESTS 1000

struct MemoryStruct {
  char *memory;
  size_t size;
};

static size_t WriteMemoryCallback(void *contents, size_t size, size_t nmemb, void *userp)
{
  size_t realsize = size * nmemb;
  struct MemoryStruct *mem = (struct MemoryStruct *)userp;

  char *ptr = realloc(mem->memory, mem->size + realsize + 1);
  if(ptr == NULL) {
    /* out of memory! */
    printf("not enough memory (realloc returned NULL)\n");
    return 0;
  }

  mem->memory = ptr;
  memcpy(&(mem->memory[mem->size]), contents, realsize);
  mem->size += realsize;
  mem->memory[mem->size] = 0;

  return realsize;
}


//vars
int noError = 0;
int error7 = 0; // CURLE_COULDNT_CONNECT
int error28 = 0;// CURLE_OPERATION_TIMEDOUT
int error35 = 0;// CURLE_SSL_CONNECT_ERROR
int error56 = 0;// CURLE_RECV_ERROR
int errorOther = 0;



//functions


int main(int argc, char *argv[])
{
    if(argc > 1 && !strcmp(argv[1], "-noprintToConsole")) printToConsole = 0;
    else printToConsole = 1;//default

    if(argc > 2 && !strcmp(argv[2], "-writeSummeryToFile")) writeSummeryToFile = 1;
    else writeSummeryToFile = 0;//default

    setenv("TZ", "UTC", 1);
    tzset();
    time_t t = time(NULL);
    struct tm tm = *localtime(&t);


    CURLM *mh;
    int still_running = 0; // keep number of running handles


    int added = 0;
    struct MemoryStruct chunk;

    time_t begin = time(NULL);

    chunk.memory = malloc(1);  /* will be grown as needed by the realloc above */
    chunk.size = 0;    /* no data at this point */

    // init a multi stack


    mh = curl_multi_init();
    do {
      CURLMcode mc; // curl_multi_wait() return code


      int numfds;
      CURLMsg *msg;
      int msgs_left;

      while(proxies[added].ip && (still_running < MAX_REQUESTS)) {
        CURL *e = curl_easy_init();
        char proxybuf[128];
        char *errormsg = malloc(CURL_ERROR_SIZE);
        snprintf(proxybuf, sizeof(proxybuf), "%s:%u",
                 proxies[added].ip, proxies[added].port);

        curl_easy_setopt(e, CURLOPT_URL, "https://www.example.com/");
        curl_easy_setopt(e, CURLOPT_CONNECTTIMEOUT_MS, 30000L);
        curl_easy_setopt(e, CURLOPT_TIMEOUT_MS, 40000L);
        curl_easy_setopt(e, CURLOPT_PROXY, proxybuf);
        curl_easy_setopt(e, CURLOPT_PROXYTYPE, proxies[added].type);
        curl_easy_setopt(e, CURLOPT_ERRORBUFFER, errormsg);
        curl_easy_setopt(e, CURLOPT_PRIVATE, errormsg);

        /* send all data to this function  */
        curl_easy_setopt(e, CURLOPT_WRITEFUNCTION, WriteMemoryCallback);
        /* we pass our 'chunk' struct to the callback function */
        curl_easy_setopt(e, CURLOPT_WRITEDATA, (void *)&chunk);

        curl_multi_add_handle(mh, e);
        still_running++;
        added++;
      }

      if(printToConsole) fprintf(stderr, "Perform %d parallel transfers\n", still_running);

      // we start some action by calling perform right away


      curl_multi_perform(mh, &still_running);

      while ((msg = curl_multi_info_read(mh, &msgs_left))) {
        if (msg->msg == CURLMSG_DONE) {
          CURL *e = msg->easy_handle;
          char *err;
          curl_easy_getinfo(e, CURLINFO_PRIVATE, &err);
          if(msg->data.result != CURLE_OK) {
              if(printToConsole)fprintf(stderr, "returned: %d - %s\n", msg->data.result, err);
          }else{
              if(printToConsole)fprintf(stderr, "NO ERROR: %d - %s\n", msg->data.result, err);
          }

          switch(msg->data.result){
              case 0:noError ++; break;
              case 28:error28 ++; break;
              case 7:error7 ++; break;
              case 35:error35++; break;
              case 56:error56++; break;
              default:errorOther++; break;
          }

          free(err);
          curl_multi_remove_handle(mh, e);
          curl_easy_cleanup(e);
        }
      }

      // wait for activity, timeout or "nothing"


      mc = curl_multi_wait(mh, NULL, 0, 1000, &numfds);
      if(mc != CURLM_OK) {
        fprintf(stderr, "curl_multi_wait() failed, code %d.\n", mc);
        break;
      }
    } while(still_running);

    curl_multi_cleanup(mh);

    if(printToConsole){
        printf("error 0:%d\n",noError);
        printf("error 7:%d\n",error7);
        printf("error 28:%d\n",error28);
        printf("error 35:%d\n",error35);
        printf("error 56:%d\n",error56);
        printf("total:%d\n",noError + error7 + error28 + error35 + error56);
        printf("added:%d\n",added);
    }

    int errorOther = added - (noError + error7 + error28 + error35 + error56);


    if(writeSummeryToFile){
        char now[200];
        char line[50000];

        int time2 =  time(NULL) - begin;


        sprintf(now, "%d-%d-%d %d:%d:%d", tm.tm_year + 1900, tm.tm_mon + 1,
                tm.tm_mday, tm.tm_hour, tm.tm_min, tm.tm_sec);

        // " 2019-02-24 18:12:44  https://www.example.com 1000    3   407 242 23.365744829178 ";

        /*
        sprintf( line,"%s\thttps://www.example.com\t1000\t30\t407\ttime:%d"
                "\tnoErrorsCount: %d, error28Cout: %d, error7Count: %d, "
                "error35Count: %d, error56Count: %d, errorOtherCount: %d\n",
              now, time2, noError, error28 , error7, error35, error56, errorOther);
        */
        sprintf( line,"%s\t%d\t%d\t%d\t%d\t%d\t%d\t%d\n",
              now, time2, noError, error28 , error7, error35, error56, errorOther);

        FILE *pFile2;


        pFile2 = fopen("cCurlTestLogs.txt", "a");

        fprintf(pFile2, line);
    }


    return 0;



}
