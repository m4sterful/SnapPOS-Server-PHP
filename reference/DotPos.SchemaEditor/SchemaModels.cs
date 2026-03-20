using System.Text.Json.Serialization;
using System.Text.Json;

namespace DotPos.SchemaEditor;

internal sealed class SchemaManifest
{
    public int SchemaFormatVersion { get; set; } = 1;
    public List<TableManifestEntry> Tables { get; set; } = [];
    public List<SeedManifestEntry> Seeds { get; set; } = [];
}

internal sealed class TableManifestEntry
{
    public string TableName { get; set; } = string.Empty;
    public string File { get; set; } = string.Empty;
    public int Version { get; set; }
    public string VersionKeyName { get; set; } = string.Empty;
}

internal sealed class SeedManifestEntry
{
    public string SeedName { get; set; } = string.Empty;
    public string File { get; set; } = string.Empty;
    public int Version { get; set; }
    public string VersionKeyName { get; set; } = string.Empty;
}

internal sealed class TableSchema
{
    public string TableName { get; set; } = string.Empty;
    public List<ColumnSchema> Columns { get; set; } = [];
    public List<IndexSchema> Indexes { get; set; } = [];
    public List<IndexSchema> UniqueIndexes { get; set; } = [];
    public List<ForeignKeySchema> ForeignKeys { get; set; } = [];
}

internal sealed class SeedSchema
{
    public string TableName { get; set; } = string.Empty;
    public string Mode { get; set; } = string.Empty;
    public List<string> MatchColumns { get; set; } = [];
    public List<string> PatchColumnsWhenEmpty { get; set; } = [];
    public List<string> ZeroIsEmptyColumns { get; set; } = [];
    public List<SeedRow> Rows { get; set; } = [];
}

internal sealed class SeedRow
{
    public Dictionary<string, JsonElement> Values { get; set; } = [];
    public List<SeedLookup> Lookups { get; set; } = [];
}

internal sealed class SeedLookup
{
    public string TargetColumn { get; set; } = string.Empty;
    public string LookupTable { get; set; } = string.Empty;
    public string LookupColumn { get; set; } = string.Empty;
    public string SelectColumn { get; set; } = "id";
    public JsonElement LookupValue { get; set; }
    public bool Required { get; set; } = true;
}

internal sealed class ColumnSchema
{
    public string Name { get; set; } = string.Empty;
    public string Type { get; set; } = string.Empty;
    public bool Nullable { get; set; } = true;

    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public string? DefaultSql { get; set; }

    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public string? OnUpdateSql { get; set; }
}

internal sealed class IndexSchema
{
    public string Name { get; set; } = string.Empty;
    public List<string> Columns { get; set; } = [];
}

internal sealed class ForeignKeySchema
{
    public string Name { get; set; } = string.Empty;
    public List<string> Columns { get; set; } = [];
    public string ReferencedTable { get; set; } = string.Empty;
    public List<string> ReferencedColumns { get; set; } = [];

    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public string? OnDelete { get; set; }

    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public string? OnUpdate { get; set; }
}

internal sealed class TableEditorRow
{
    public string Name { get; set; } = string.Empty;
    public string Type { get; set; } = string.Empty;
    public bool Nullable { get; set; } = true;
    public string DefaultSql { get; set; } = string.Empty;
    public string OnUpdateSql { get; set; } = string.Empty;
}

internal sealed class IndexEditorRow
{
    public string Name { get; set; } = string.Empty;
    public string Columns { get; set; } = string.Empty;
}

internal sealed class ForeignKeyEditorRow
{
    public string Name { get; set; } = string.Empty;
    public string Columns { get; set; } = string.Empty;
    public string ReferencedTable { get; set; } = string.Empty;
    public string ReferencedColumns { get; set; } = string.Empty;
    public string OnDelete { get; set; } = string.Empty;
    public string OnUpdate { get; set; } = string.Empty;
}

internal sealed class TableDocument
{
    public required TableManifestEntry ManifestEntry { get; init; }
    public required TableSchema Schema { get; init; }
    public required string TablePath { get; init; }
}

internal sealed class SaveTableResult
{
    public required bool TableChanged { get; init; }
    public required int NewVersion { get; init; }
}

internal sealed class SeedDocument
{
    public required SeedManifestEntry ManifestEntry { get; init; }
    public required SeedSchema Schema { get; init; }
    public required string SeedPath { get; init; }
}

internal sealed class SaveSeedResult
{
    public required bool SeedChanged { get; init; }
    public required int NewVersion { get; init; }
}
