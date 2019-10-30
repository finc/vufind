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
	mkdir -p tmp
	rm -f make/*.d
	mv css/* tmp
	make all
	rm -f tmp/*
css/%.css: less/%.less 
	lessc -s -M '$<' '$@' > 'make/$*.d'
	mkdir -p tmp
	lessc -s $< --clean-css="--s0 --advanced" $(CSSMAP) TMP
	mv -u TMP $@
        sed -i "s/sourceMappingURL=css/sourceMappingURL=/themes/bodensee/css/g" $@
-include $(DEPEND)
all: $(TARGETS)

