<?php

declare(strict_types=1);

namespace App\Enums;

/** Every sensitive action written to the audit log (docs/requirements.md, 8.3). */
enum AuditAction: string
{
    case LoginSucceeded = 'auth.login_succeeded';
    case LoginFailed = 'auth.login_failed';
    case Logout = 'auth.logout';
    case RequestCreated = 'request.created';
    case RequestSubmitted = 'request.submitted';
    case RequestCancelled = 'request.cancelled';
    case RequestApproved = 'request.approved';
    case RequestRejected = 'request.rejected';
    case RequestInfoRequested = 'request.info_requested';
    case RequestResponseSubmitted = 'request.response_submitted';
    case AuthorizationGenerated = 'authorization.generated';
    case AuthorizationViewed = 'authorization.viewed';
    case AuthorizationDownloaded = 'authorization.downloaded';
    case DocumentUploaded = 'document.uploaded';
    case DocumentDownloaded = 'document.downloaded';
    case FacilityInsurersUpdated = 'facility.insurers_updated';
    case CoverageRecordCreated = 'coverage_record.created';
    case CoverageRecordUpdated = 'coverage_record.updated';
    case CoverageRosterImported = 'coverage_roster.imported';
    case RequestUpdated = 'request.updated';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case InactiveAccountSessionEnded = 'auth.inactive_session_ended';
    case MedicalActCreated = 'medical_act.created';
    case MedicalActUpdated = 'medical_act.updated';
    case OfficialTariffCreated = 'official_tariff.created';
    case OrganizationCreated = 'organization.created';
    case OrganizationRenamed = 'organization.renamed';
}
