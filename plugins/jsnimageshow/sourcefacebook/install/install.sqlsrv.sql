SET QUOTED_IDENTIFIER ON;

IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[#__imageshow_external_source_facebook]') AND type in (N'U'))
BEGIN
CREATE TABLE [#__imageshow_external_source_facebook](
	[external_source_id] [int] IDENTITY(1,1) NOT NULL,
    [external_source_profile_title] [nvarchar](255) NULL,
    [facebook_user_id] [nvarchar](20) NULL,
	[facebook_access_token] [nvarchar](255) NULL,
	[facebook_app_id] [nvarchar](255) NULL,
	[facebook_app_secret] [nvarchar](255) NULL,
    [facebook_thumbnail_size] [nvarchar](30) NULL,
    [facebook_image_size] [nvarchar](30) NULL,
 CONSTRAINT [PK_#__imageshow_external_source_facebook_external_source_id] PRIMARY KEY CLUSTERED
(
	[external_source_id] ASC
)WITH (STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF)
)
END;