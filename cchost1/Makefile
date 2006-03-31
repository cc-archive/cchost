# Makefile for ccHost distribution and packaging
#
# This original Makefile was created for Gotmail, http://gotmail.sf.net/ and
# is used here in accordance with the GNU GPL which is copied in the COPYING 
# file accompanying this file.
# 
# Copyright 2005, Jon Phillips & Paul Howarth (http://gotmail.sf.net/).
# Copyright 2005, Creative Commons.
#
# It generates the distribution packages but does not
# include "make install" type functionality itself.
#
# TODO: Make sure that the user changes user:group with chown before building 
# packages.
# 
# TODO: Fix the script to build RPM packages better.
# 
# TODO: It generates RPMs fine, but removed from make dist...add back!

RELEASE_NUM = $(shell cat VERSION)
APPNAME = cchost
PACKAGEDIR=packages

DIST_FILES_STATIC=AUTHORS \
	ChangeLog \
	COPYING \
	README \
	VERSION \
	cc-custom.php \
	cc-error-msg.txt \
	cc-errors.txt \
	cc-includes.php \
	cc-non-ui.php \
	index.php

DIST_FOLDERS=ccadmin \
	ccextras \
	ccfiles \
	ccimages \
	cclib \
	cctemplates \
	cctools \
	forum \
	mixter-files \
	mixter-lib \
	bin
	
DIST_FILES_GENERATED=cchost.spec \
	NEWS \
	PRESS

DIST_FILES_OTHER=Makefile.dist

all:	$(DIST_FILES_GENERATED) $(DIST_FILES_OTHER)
	$(MAKE) -f Makefile.dist all

install: all
	$(MAKE) -f Makefile.dist install

uninstall:
	$(MAKE) -f Makefile.dist uninstall

clean:
	rm -f $(DIST_FILES_GENERATED)
	$(MAKE) -f Makefile.dist clean

#$(APPNAME): $(APPNAME).in
#	sed -e "s/PROJECT_VERSION/$(RELEASE_NUM)/" $(APPNAME).in > $(APPNAME)
#	chmod 755 $(APPNAME)

% :: %.in
	sed -e "s/PROJECT_VERSION/$(RELEASE_NUM)/" $@.in > $@

distprep: all
	@echo "If there are permission errors, run bin/fix_permissions.sh."
	# bin/backup_configs.sh
	# get rid of the cache
	rm -Rf cclib/phptal/phptal_cache/*.php
	rm -Rf $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM)
	mkdir -p $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM)
	cp -Rfp $(DIST_FILES_STATIC) $(DIST_FILES_GENERATED) $(DIST_FOLDERS) \
		$(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM)
	cp -p Makefile.dist $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM)/Makefile
	# Get rid of all CVS folders in the packaging area
	find $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM) \
	    -depth -name CVS -type d -exec rm -rf {} \;
	# chmod 644 $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM)/*
	# chmod 755 $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM)/$(APPNAME)

distclean: clean
	rm -f *.tar.gz *.zip *.rpm *.tar.bz2
	rm -rf $(APPNAME)-$(RELEASE_NUM)
	rm -rf $(PACKAGEDIR)

rpm:	bzip
	rpmbuild --define "_rpmdir `pwd`" \
		 --define '_build_name_fmt %%{NAME}-%%{VERSION}-%%{RELEASE}.%%{ARCH}.rpm' \
		 -tb $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM).tar.gz
	mv *.rpm $(PACKAGEDIR)

srpm:	bzip
	rpmbuild --define "_srcrpmdir `pwd`" \
		 -ts $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM).tar.gz
	mv *.rpm $(PACKAGEDIR)

rpms:	bzip
	rpmbuild --define "_rpmdir `pwd`" \
		 --define "_srcrpmdir `pwd`" \
		 --define '_build_name_fmt %%{NAME}-%%{VERSION}-%%{RELEASE}.%%{ARCH}.rpm' \
		 -ta $(PACKAGEDIR)/$(APPNAME)-$(RELEASE_NUM).tar.gz
	mv *.rpm $(PACKAGEDIR)

#deb: rpm
	# alien $(APPNAME)-*.rpm

zip: distprep
	(cd $(PACKAGEDIR); zip -r $(APPNAME)-$(RELEASE_NUM).zip $(APPNAME)-$(RELEASE_NUM))

tarball: distprep
	(cd $(PACKAGEDIR); tar czf $(APPNAME)-$(RELEASE_NUM).tar.gz $(APPNAME)-$(RELEASE_NUM))

bzip: distprep
	(cd $(PACKAGEDIR); tar -cjf $(APPNAME)-$(RELEASE_NUM).tar.bz2 $(APPNAME)-$(RELEASE_NUM))

# TODO: Add rpms back to the dist: directive
dist: tarball zip bzip

test:
	@echo "BINDIR: $(BINDIR)"
	@echo "MANDIR: $(MANDIR)"
	@echo "MAN1DIR: $(MAN1DIR)"
	@echo "INSTALL: $(INSTALL)"

.PHONY: all install uninstall clean distprep distclean rpm srpm rpms deb zip dist tarball bzip test

