include config.mk

OBJECTS = squirrelmail_autoresponder_forwarder_proxy.o
LIBS = $(LCRYPT) $(LPAM)

squirrelmail_autoresponder_forwarder_proxy: $(OBJECTS)
	cc -o squirrelmail_autoresponder_forwarder_proxy $(LFLAGS) $(OBJECTS) $(LIBS)

install: squirrelmail_autoresponder_forwarder_proxy 
	install -g root -o root -m 4755 squirrelmail_autoresponder_forwarder_proxy $(BINDIR)

clean:
	rm -f *.o *~* core squirrelmail_autoresponder_forwarder_proxy 

squirrelmail_autoresponder_forwarder_proxy.o: squirrelmail_autoresponder_forwarder_proxy.c
	cc -c $(CFLAGS) $(DEBUG) $(USESHADOW) $(USEPAM) $(ROOT_ALLOWED) $(RESTRICTUSE) -D WEBUSER=\"$(WEBUSER)\" squirrelmail_autoresponder_forwarder_proxy.c
