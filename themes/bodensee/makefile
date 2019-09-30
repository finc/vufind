# Makefile for creating Stylesheets
# http://www.thumbtack.com/engineering/makefiles-for-less-and-css/

# Just call make all
SOURCES = $(shell ls less/*.less)
SOURCES := $(filter-out less/vufind.less, $(SOURCES))
TARGETS = $(patsubst less/%.less,css/%.css,$(SOURCES))
TMP		= $(patsubst css/, tmp/,$(TARGETS))
DEPEND = $(patsubst less/%.less,make/%.d,$(SOURCES))
TMP = $(patsubst css/, tmp/,$@)

ifeq ($(NOMAP),true)
	CSSMAP = 
else 
	CSSMAP = --source-map="$@.map"
endif
# for debugging. usage: make print-VARIABLE
print-%:
	@echo '$*=$($*)'
clean: 
	rm -f make/*.d
	rm -f css/*.map
	rm -f css/*.css
	make all
css/%.css: less/%.less 
	lessc -s -M '$<' '$@' > 'make/$*.d'
	mkdir -p tmp
	lessc -s $< --clean-css="--s0 --advanced" $(CSSMAP) TMP
	mv -u TMP $@
-include $(DEPEND)
all: $(TARGETS)

