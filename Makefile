PYTHON ?= python3
PHP ?= php
.PHONY: check package
check:
	$(PYTHON) scripts/release.py check --php "$(PHP)"
package:
	$(PYTHON) scripts/release.py package
