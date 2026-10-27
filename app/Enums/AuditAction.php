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
    case DocumentUploaded = 'document.uploaded';
    case DocumentDownloaded = 'document.downloaded';
    case FacilityInsurersUpdated = 'facility.insurers_updated';
    case CoverageRecordCreated = 'coverage_record.created';
    case CoverageRecordUpdated = 'coverage_record.updated';
    case CoverageRosterImported = 'coverage_roster.imported';
}
