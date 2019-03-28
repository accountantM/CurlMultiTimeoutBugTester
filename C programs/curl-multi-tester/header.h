#ifndef HEADER_FILE
#define HEADER_FILE

#ifdef _WIN32
#define WAITMS(x) Sleep(x)
#else
/* Portable sleep for platforms other than Windows. */
#define WAITMS(x)                               \
  struct timeval wait = { 0, (x) * 1000 };      \
  (void)select(0, NULL, NULL, NULL, &wait);
#endif

struct one {
    const char *ip;
    int port;
    int type;
};

#endif

