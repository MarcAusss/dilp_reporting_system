# DILP Legacy Spreadsheet Migration Guide

## Purpose
The legacy importer moves project-level records from CSV/XLSX spreadsheets into the normalized DILP database without overwriting existing projects.

## Recommended migration sequence
1. Back up the production database and the original workbook.
2. Confirm Master Data names/codes (Project Type, Project Purpose, offices, fund sources, locations, livelihoods).
3. Open **Administration → Legacy Data Import**.
4. Upload one CSV/XLSX batch at a time.
5. Review automatic column mapping and correct any unmatched headers.
6. Run **Dry-Run Validation**.
7. Resolve errors by correcting Master Data or the source spreadsheet, then upload/revalidate.
8. Review duplicate rows. Duplicates are never overwritten and are skipped on commit.
9. Commit the batch only when no blocking errors remain.
10. Open **Administration → Data Quality** and resolve post-import completeness issues.
11. Compare the Phase 7 Summary/Per Fund/Per PO reports with the original spreadsheet totals.

## Required project mappings
- Fiscal Year
- Project Title
- Proponent Name
- Project Type
- Project Purpose

## Important behavior
- Existing project codes and strong same-project matches are flagged as duplicates.
- Proponents may be created automatically if an exact name match does not exist.
- Master Data references are never invented automatically.
- Aggregate target beneficiaries can be preserved on an imported livelihood entry when a livelihood column is mapped.
- Individual beneficiary records should be encoded/imported separately when personally identifiable beneficiary-level source data is available.
- Import source files are stored on the private local filesystem.
- Every upload, validation, commit, and imported project is recorded in Audit Logs.
