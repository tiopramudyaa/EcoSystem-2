---
key: dl-project-detail
chapter: delivery
order: 11
title: Project detail: General, Delivery Info, Team, Documents and Location
summary: Read and update one project: general information, sales data and payment terms, team members, documents and location.
requires: [delivery-project.general.view, delivery-project.delivery-data.view, delivery-project.delivery-info.view, delivery-project.location.view, delivery-project.team.view, delivery-project.documents.view]
updated: 2026-11-25
---
The project detail page is one long page. The tabs on top (`General`, `Delivery Info`, `Stakeholders`, `Team`, `Documents`, `Issues`, `Risks`, `Location`, `Planning`, `WRICEF`, `Plan Cost`) scroll to a section. This topic covers the first group. The registers and the plan are in the next two topics.

## Who uses this

Project heads, project administrators and the team of the project. Each section has its own `view`, `edit` and `create or delete` permission in Menu Access. A section you may not see is simply missing, and a pencil or `Add` button you may not use is hidden.

## Before you start

- A closed project is locked. Reopen it first if you need to change it.
- Revenue and payment terms are financial data. Check them before you save.

## Step by step

![The Project Detail page with the tabs on top and the General Information card](images/guide/dlv-project-detail.png)

*Screenshot with sample data.*

1. Open `Delivery` › `Project` and click a project. The title bar shows the project and customer, with the buttons `Create Folder`, `Close Project` and `Delete Project`.
2. In `General Information` read the customer, name, owner, type, IO number, category, phase, contract dates and go-live estimate. Choose the pencil icon to edit. `Category`, `Phase` and `Go Live Estimated` are filled from the planning and cannot be typed.
3. `Delivery Data` holds the delivery method, the warranty period and the total mandays.

![The Delivery Information card with sales data and the Term Of Payment plan](images/guide/dlv-project-delivery-info.png)

*Screenshot with sample data.*

4. In `Delivery Information` the `Sales Data` shows the account executive, `Revenue`, `Plan Cost`, `Gross Profit`, its percentage and the actual figures. Choose the pencil to edit.
5. Under it, choose the `TOP Type`: `% of Revenue` (the amount follows the payment percentage) or `Contract Line Item`. Then use `Add Payment Term` to add each term with its percentage or amount, requirement, estimated date, invoice data, paid date and status. The footer shows the total and warns when the terms are below or above the revenue. `Export Excel` downloads the table.

![The Team Members table with name, position, module, role and period](images/guide/dlv-project-team.png)

*Screenshot with sample data.*

6. In `Team Members` choose `Add Team Member` to add a person with a module, a role and a period. Use the row actions to change or remove a member.
7. In `Project Documents` choose `Create Folder` in the title bar first: documents are stored in a OneDrive folder and the page says so until it exists. Documents are grouped in folders: `01. Discover`, `02. Prepare`, `03. Explore`, `04. Realize`, `05. Deploy`, `06. Run` and `00. Other`, followed by the folders your team added. Each folder shows how many documents it holds. Click a folder to open or close it, or use `Expand all` and `Collapse all`.
   - To upload, choose `Upload Document` and click one of the folders under `Folder`. The chosen folder is highlighted and is added at the end of `TUJUAN UPLOAD`, which shows the full path in OneDrive. Then pick the file and the `Document Type` and choose `Upload`. The upload icon on a folder opens the same window with that folder already chosen.
   - To add a folder, choose `Add Folder`, type only the name (for example `Change Request`) and choose `Save`. The number is given for you: the first added folder is `11`, then `12`, `13` and so on.
   - On a folder you added, the pencil icon renames it and the bin icon deletes it.
   - To change or delete documents, tick them (or tick `Select all documents`) and use `Edit` or `Delete` in the bar that appears. In `Edit` you can change the name, the `Folder` and the `Document Type`, or replace the file.
8. `Location Information` holds the location name, address type, country, region, city, validity and street. Choose the pencil to edit.

## Rules to know

### Project Documents

- The standard folders (`01. Discover` to `06. Run` and `00. Other`) exist in every project and cannot be renamed or deleted.
- An added folder gets the number after the highest added number still in the project, starting at 11. A gap in the middle is not filled again; when the newest added folder is deleted, the next new folder gets its number.
- A folder name is required, has at most 100 characters and must be different from every other folder of the project, standard folders included (upper and lower case count as the same). It cannot contain `\ / : * ? " < > |` and cannot start with a number and a dot, because the number is added for you.
- A folder can only be deleted when it holds no documents. Move or delete its documents first. Its folder in OneDrive is removed only when it is empty there too; otherwise it stays in OneDrive.
- Every folder is a sub-folder of the project folder in OneDrive. The sub-folders are made together with the project folder, and at the latest on the first upload into a folder.
- Choosing a folder is required when you upload. A file can be up to 100 MB.
- Moving a document to another folder in `Edit` also moves its file in OneDrive, and the link stays the same. A replacement file is uploaded into the chosen folder.
- Documents uploaded before the folders existed are in `00. Other`. Moving them changes only the folder in EcoSystem; their file stays where it is in OneDrive.
- Supporting documents of `Plan Cost` expenses are stored in OneDrive under `01. Discover` › `Plan Cost`. An older `Plan Cost` folder directly in the project folder is moved there automatically on the next supporting document upload in `Plan Cost` or when `Create Folder` is used again, and its links keep working.
- Uploading documents, adding or deleting a folder and deleting documents need the create or delete permission of the documents section. Renaming a folder and editing a document need its edit permission. A closed project cannot be changed.

## What happens next

Saving a section updates the project at once and refreshes `Last Update`. The financial figures feed reports such as `Collection Outlook`.

## Common questions

**Documents cannot be uploaded.** Create the folder first with `Create Folder`.

**Where are the documents uploaded before the folders?** In `00. Other`. Use `Edit` to put them in the right folder.

**I cannot delete a folder.** Standard folders cannot be deleted, and an added folder must be empty first. Move or delete its documents, then delete the folder.

**I cannot find the Plan Cost files in OneDrive.** They are now in `01. Discover` › `Plan Cost` inside the project folder.

**I can see Delivery Info but not edit it.** You have `view` but not `edit`. Ask for the permission if your work needs it.

**A payment term total does not match the revenue.** The footer tells you how much is missing or extra. Adjust a term or the revenue.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, resources/views/delivery/project/projects/partials/documents-section.blade.php, app/Http/Controllers/DeliveryProjectController.php (storeDocumentFolder, updateDocumentFolder, destroyDocumentFolder, updateDocument, createDocumentUploadSession), app/Models/DeliveryProjectDocumentFolder.php, app/Services/ProjectDocumentFolderService.php, routes/web.php -->
