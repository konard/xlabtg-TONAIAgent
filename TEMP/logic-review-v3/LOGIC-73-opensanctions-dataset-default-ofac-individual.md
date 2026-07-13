# LOGIC-73 — OpenSanctions result mapping defaults unknown datasets to ofac_sdn and hardcodes every match as an individual, corrupting match provenance and entity type

**Severity:** 🟡 Low
**Area:** Data
**Stage:** Stage 4 — Market data & connector integrity
**Suggested labels:** `bug`, `data`, `severity:low`, `area:regulatory`, `stage:4-connector-integrity`, `audit:logic-review-v3`
**Location:** `services/regulatory/providers/opensanctions.ts:72-77, 179`
**Filed as:** [#521](https://github.com/xlabtg/TONAIAgent/issues/521)

## Problem
`datasetToList` returns `'ofac_sdn'` for any unmapped dataset, and each mapped match sets `entityType: 'individual' as const` regardless of the real schema.

## Evidence
```ts
function datasetToList(dataset: string): SanctionsList {
  for (const [prefix, list] of Object.entries(DATASET_TO_LIST)) {
    if (dataset.startsWith(prefix)) return list;
  }
  return 'ofac_sdn';
}
```
```ts
        entityType: 'individual' as const,
```

## Impact
A hit from an unmapped list (e.g. Swiss SECO, Australian DFAT) is recorded to compliance as an OFAC-SDN match (wrong authority), and sanctioned organizations/vessels are recorded as individuals — corrupting KYC/screening reports and any entityType branching. Screening still fails closed (trade still blocked), so this is a data-integrity/reporting defect, not a missed hit.

## Suggested fix
Return an explicit unknown/other SanctionsList (or carry the raw dataset id) instead of defaulting to ofac_sdn, and derive entityType from the OpenSanctions schema field (Person vs Organization/Vessel).

## Acceptance criteria
- [ ] An unmapped dataset is not labeled ofac_sdn
- [ ] entityType reflects the source schema
- [ ] Regression test: asserts provenance and entityType for a non-OFAC organization hit
```
