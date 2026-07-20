.PHONY: build release

# Build the self-contained PHAR into builds/overtimely.
# Usage: make build VERSION=1.0.0   (VERSION optional; prompts if omitted)
build:
	php overtimely app:build overtimely --build-version=$(VERSION)

# Rebuild the PHAR, commit it, and tag the release. Commit code changes first.
# Usage: make release VERSION=1.0.0
release:
ifndef VERSION
	$(error VERSION is required, e.g. make release VERSION=1.0.0)
endif
	php overtimely app:build overtimely --build-version=$(VERSION)
	git add builds/overtimely
	git commit -m "builds $(VERSION)"
	git tag $(VERSION)
	git push
	git push origin $(VERSION)
	@echo ""
	@echo "Released and pushed $(VERSION)."
