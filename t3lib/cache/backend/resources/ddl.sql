BEGIN;

CREATE TABLE "cache" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "scope" CHAR(12) NOT NULL,
  "created" INTEGER UNSIGNED NOT NULL,
  "lifetime" INTEGER UNSIGNED DEFAULT '0' NOT NULL,
  "content" TEXT,
  PRIMARY KEY ("identifier", "cache", "scope")
);

CREATE TABLE "tags" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "scope" CHAR(12) NOT NULL,
  "tag" VARCHAR(250) NOT NULL
);
CREATE INDEX "identifier" ON "tags" ("identifier", "cache", "scope");
CREATE INDEX "tag" ON "tags" ("tag");

COMMIT;
