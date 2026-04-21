# Task: Create create_facture.py automation

## Steps:
- [x] Create automatisation/ies/facture/params.json
- [x] Create automatisation/ies/facture/create_facture.py
- [ ] Verify aws cli configured for B2 (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY env vars)
- [ ] Test: export BL_NUMBER=S329087393; python automatisation/ies/facture/create_facture.py
- [ ] Integrate with Spring Boot app if needed (endpoint to trigger)

## Notes:
- Uses today date %d-%m-%Y for B2 path.
- Assumes `aws` CLI installed and B2 creds in env.
- Direct download, no generation.
