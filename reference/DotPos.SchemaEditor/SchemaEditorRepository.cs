using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace DotPos.SchemaEditor;

internal sealed class SchemaEditorRepository
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true,
        ReadCommentHandling = JsonCommentHandling.Skip,
        AllowTrailingCommas = true
    };

    private static readonly JsonSerializerOptions WriteOptions = new()
    {
        WriteIndented = true,
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull
    };

    public SchemaEditorRepository(string schemaRoot)
    {
        SchemaRoot = schemaRoot;
        ManifestPath = Path.Combine(schemaRoot, "schema.json");
    }

    public string SchemaRoot { get; }
    public string ManifestPath { get; }

    public static SchemaEditorRepository CreateDefault()
    {
        var current = new DirectoryInfo(AppContext.BaseDirectory);
        while (current != null)
        {
            var solutionPath = Path.Combine(current.FullName, "DotPos.sln");
            var schemaRoot = Path.Combine(current.FullName, "apps", "api", "DotPos.Api", "LocalDatabaseSchema");
            var manifestPath = Path.Combine(schemaRoot, "schema.json");
            if (File.Exists(solutionPath) && File.Exists(manifestPath))
            {
                return new SchemaEditorRepository(schemaRoot);
            }

            current = current.Parent;
        }

        throw new InvalidOperationException("Could not locate apps\\api\\DotPos.Api\\LocalDatabaseSchema\\schema.json from the application folder.");
    }

    public SchemaManifest LoadManifest()
    {
        if (!File.Exists(ManifestPath))
        {
            throw new InvalidOperationException($"Schema manifest not found at '{ManifestPath}'.");
        }

        return DeserializeFile<SchemaManifest>(ManifestPath, "schema manifest");
    }

    public IReadOnlyList<TableManifestEntry> GetOrderedTables(SchemaManifest manifest)
    {
        return manifest.Tables
            .OrderBy(table => table.TableName, StringComparer.OrdinalIgnoreCase)
            .ToList();
    }

    public IReadOnlyList<SeedManifestEntry> GetOrderedSeeds(SchemaManifest manifest)
    {
        return manifest.Seeds
            .OrderBy(seed => seed.SeedName, StringComparer.OrdinalIgnoreCase)
            .ToList();
    }

    public TableDocument LoadTable(TableManifestEntry entry)
    {
        var tablePath = ResolvePath(entry.File);
        var table = DeserializeFile<TableSchema>(tablePath, $"table '{entry.TableName}'");
        return new TableDocument
        {
            ManifestEntry = CloneManifestEntry(entry),
            Schema = CloneTableSchema(table),
            TablePath = tablePath
        };
    }

    public SeedDocument LoadSeed(SeedManifestEntry entry)
    {
        var seedPath = ResolvePath(entry.File);
        var seed = DeserializeFile<SeedSchema>(seedPath, $"seed '{entry.SeedName}'");
        return new SeedDocument
        {
            ManifestEntry = CloneManifestEntry(entry),
            Schema = CloneSeedSchema(seed),
            SeedPath = seedPath
        };
    }

    public TableDocument AddTable(SchemaManifest manifest, string tableName)
    {
        if (string.IsNullOrWhiteSpace(tableName))
        {
            throw new InvalidOperationException("Table name is required.");
        }

        if (manifest.Tables.Any(table => table.TableName.Equals(tableName, StringComparison.OrdinalIgnoreCase)))
        {
            throw new InvalidOperationException($"Table '{tableName}' already exists.");
        }

        var fileName = $"{SanitizeFileName(tableName)}.table.json";
        var relativePath = Path.Combine("tables", fileName).Replace('\\', '/');
        var entry = new TableManifestEntry
        {
            TableName = tableName,
            File = relativePath,
            Version = 1
        };
        var table = new TableSchema
        {
            TableName = tableName,
            Columns =
            [
                new ColumnSchema
                {
                    Name = "new_column",
                    Type = "TEXT",
                    Nullable = true
                }
            ],
            Indexes = [],
            UniqueIndexes = [],
            ForeignKeys = []
        };

        manifest.Tables.Add(entry);
        Directory.CreateDirectory(Path.GetDirectoryName(ResolvePath(relativePath))!);
        SaveManifest(manifest);
        SaveTableSchema(ResolvePath(relativePath), table);

        return new TableDocument
        {
            ManifestEntry = CloneManifestEntry(entry),
            Schema = CloneTableSchema(table),
            TablePath = ResolvePath(relativePath)
        };
    }

    public SeedDocument AddSeed(SchemaManifest manifest, string seedName)
    {
        if (string.IsNullOrWhiteSpace(seedName))
        {
            throw new InvalidOperationException("Seed name is required.");
        }

        if (manifest.Seeds.Any(seed => seed.SeedName.Equals(seedName, StringComparison.OrdinalIgnoreCase)))
        {
            throw new InvalidOperationException($"Seed '{seedName}' already exists.");
        }

        var defaultTableName = manifest.Tables.FirstOrDefault()?.TableName
            ?? throw new InvalidOperationException("At least one table is required before adding a seed.");

        var fileName = $"{SanitizeFileName(seedName)}.seed.json";
        var relativePath = Path.Combine("seeds", fileName).Replace('\\', '/');
        var entry = new SeedManifestEntry
        {
            SeedName = seedName,
            File = relativePath,
            Version = 1
        };
        var seed = new SeedSchema
        {
            TableName = defaultTableName,
            Mode = "ensure_missing_rows",
            MatchColumns = [],
            PatchColumnsWhenEmpty = [],
            ZeroIsEmptyColumns = [],
            Rows =
            [
                new SeedRow
                {
                    Values = new Dictionary<string, JsonElement>(StringComparer.OrdinalIgnoreCase)
                    {
                        ["example_key"] = JsonSerializer.SerializeToElement("value")
                    },
                    Lookups = []
                }
            ]
        };

        manifest.Seeds.Add(entry);
        Directory.CreateDirectory(Path.GetDirectoryName(ResolvePath(relativePath))!);
        SaveManifest(manifest);
        SaveSeedSchema(ResolvePath(relativePath), seed);

        return new SeedDocument
        {
            ManifestEntry = CloneManifestEntry(entry),
            Schema = CloneSeedSchema(seed),
            SeedPath = ResolvePath(relativePath)
        };
    }

    public void DeleteTable(SchemaManifest manifest, TableManifestEntry entry)
    {
        var manifestEntry = manifest.Tables.SingleOrDefault(
            table => table.TableName.Equals(entry.TableName, StringComparison.OrdinalIgnoreCase));
        if (manifestEntry == null)
        {
            return;
        }

        manifest.Tables.Remove(manifestEntry);
        SaveManifest(manifest);

        var tablePath = ResolvePath(manifestEntry.File);
        if (File.Exists(tablePath))
        {
            File.Delete(tablePath);
        }
    }

    public void DeleteSeed(SchemaManifest manifest, SeedManifestEntry entry)
    {
        var manifestEntry = manifest.Seeds.SingleOrDefault(
            seed => seed.SeedName.Equals(entry.SeedName, StringComparison.OrdinalIgnoreCase));
        if (manifestEntry == null)
        {
            return;
        }

        manifest.Seeds.Remove(manifestEntry);
        SaveManifest(manifest);

        var seedPath = ResolvePath(manifestEntry.File);
        if (File.Exists(seedPath))
        {
            File.Delete(seedPath);
        }
    }

    public SaveTableResult SaveTable(SchemaManifest manifest, TableDocument originalDocument, TableSchema updatedSchema)
    {
        ValidateTable(updatedSchema);

        var manifestEntry = manifest.Tables.Single(
            table => table.TableName.Equals(originalDocument.ManifestEntry.TableName, StringComparison.OrdinalIgnoreCase));

        var changed = IsTableChanged(originalDocument, updatedSchema);

        manifestEntry.TableName = updatedSchema.TableName;
        manifestEntry.File = originalDocument.ManifestEntry.File;
        if (changed)
        {
            manifestEntry.Version = manifestEntry.Version <= 0 ? 1 : manifestEntry.Version + 1;
        }

        SaveTableSchema(originalDocument.TablePath, updatedSchema);
        SaveManifest(manifest);

        return new SaveTableResult
        {
            TableChanged = changed,
            NewVersion = manifestEntry.Version
        };
    }

    public SaveSeedResult SaveSeed(
        SchemaManifest manifest,
        SeedDocument originalDocument,
        SeedManifestEntry updatedEntry,
        SeedSchema updatedSchema)
    {
        var manifestEntry = manifest.Seeds.Single(
            seed => seed.SeedName.Equals(originalDocument.ManifestEntry.SeedName, StringComparison.OrdinalIgnoreCase));

        ValidateSeedManifestEntry(manifest, originalDocument.ManifestEntry.SeedName, updatedEntry);
        ValidateSeed(manifest, updatedEntry.SeedName, updatedSchema);

        var changed = IsSeedChanged(originalDocument, updatedEntry, updatedSchema);

        manifestEntry.SeedName = updatedEntry.SeedName;
        manifestEntry.File = originalDocument.ManifestEntry.File;
        manifestEntry.VersionKeyName = updatedEntry.VersionKeyName;
        if (changed)
        {
            manifestEntry.Version = manifestEntry.Version <= 0 ? 1 : manifestEntry.Version + 1;
        }

        SaveSeedSchema(originalDocument.SeedPath, updatedSchema);
        SaveManifest(manifest);

        return new SaveSeedResult
        {
            SeedChanged = changed,
            NewVersion = manifestEntry.Version
        };
    }

    public bool IsTableChanged(TableDocument originalDocument, TableSchema updatedSchema)
    {
        var originalSignature = SerializeNormalized(originalDocument.Schema);
        var updatedSignature = SerializeNormalized(updatedSchema);
        return !string.Equals(originalSignature, updatedSignature, StringComparison.Ordinal);
    }

    public bool IsSeedChanged(
        SeedDocument originalDocument,
        SeedManifestEntry updatedEntry,
        SeedSchema updatedSchema)
    {
        var originalSignature = SerializeNormalized(originalDocument.ManifestEntry, originalDocument.Schema);
        var updatedManifestSignature = SerializeNormalized(updatedEntry, updatedSchema);
        return !string.Equals(originalSignature, updatedManifestSignature, StringComparison.Ordinal);
    }

    private void SaveManifest(SchemaManifest manifest)
    {
        var json = JsonSerializer.Serialize(manifest, WriteOptions);
        File.WriteAllText(ManifestPath, json + Environment.NewLine, Encoding.UTF8);
    }

    private static void SaveTableSchema(string path, TableSchema table)
    {
        var json = JsonSerializer.Serialize(table, WriteOptions);
        File.WriteAllText(path, json + Environment.NewLine, Encoding.UTF8);
    }

    private static void SaveSeedSchema(string path, SeedSchema seed)
    {
        var json = JsonSerializer.Serialize(seed, WriteOptions);
        File.WriteAllText(path, json + Environment.NewLine, Encoding.UTF8);
    }

    private string ResolvePath(string relativePath)
    {
        var fullPath = Path.GetFullPath(Path.Combine(SchemaRoot, relativePath));
        if (!fullPath.StartsWith(SchemaRoot, StringComparison.OrdinalIgnoreCase))
        {
            throw new InvalidOperationException($"Schema path '{relativePath}' resolves outside the schema root.");
        }

        return fullPath;
    }

    private static T DeserializeFile<T>(string path, string description)
    {
        try
        {
            var json = File.ReadAllText(path);
            var value = JsonSerializer.Deserialize<T>(json, JsonOptions);
            return value ?? throw new InvalidOperationException($"Could not deserialize {description} at '{path}'.");
        }
        catch (JsonException ex)
        {
            throw new InvalidOperationException($"Invalid JSON in {description} at '{path}'.", ex);
        }
    }

    private static string SerializeNormalized(TableSchema table)
    {
        return JsonSerializer.Serialize(table, WriteOptions);
    }

    private static string SerializeNormalized(SeedSchema seed)
    {
        return JsonSerializer.Serialize(seed, WriteOptions);
    }

    private static string SerializeNormalized(SeedManifestEntry entry, SeedSchema seed)
    {
        return JsonSerializer.Serialize(
            new
            {
                entry.SeedName,
                entry.VersionKeyName,
                seed.TableName,
                seed.Mode,
                seed.MatchColumns,
                seed.PatchColumnsWhenEmpty,
                seed.ZeroIsEmptyColumns,
                seed.Rows
            },
            WriteOptions);
    }

    private static void ValidateTable(TableSchema table)
    {
        if (string.IsNullOrWhiteSpace(table.TableName))
        {
            throw new InvalidOperationException("Table name is required.");
        }

        if (table.Columns.Count == 0)
        {
            throw new InvalidOperationException($"Table '{table.TableName}' must have at least one column.");
        }

        var seen = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        foreach (var column in table.Columns)
        {
            if (string.IsNullOrWhiteSpace(column.Name))
            {
                throw new InvalidOperationException($"Table '{table.TableName}' has a column with no name.");
            }

            if (column.Name.Equals("id", StringComparison.OrdinalIgnoreCase))
            {
                throw new InvalidOperationException("Do not add an 'id' column. The initializer creates it automatically.");
            }

            if (!seen.Add(column.Name))
            {
                throw new InvalidOperationException($"Table '{table.TableName}' contains duplicate column '{column.Name}'.");
            }

            if (string.IsNullOrWhiteSpace(column.Type))
            {
                throw new InvalidOperationException($"Column '{column.Name}' must define a SQL type.");
            }
        }

        ValidateIndexes(table, table.Indexes, "index");
        ValidateIndexes(table, table.UniqueIndexes, "unique index");
        ValidateForeignKeys(table, seen);
    }

    private void ValidateSeed(SchemaManifest manifest, string originalSeedName, SeedSchema seed)
    {
        if (string.IsNullOrWhiteSpace(originalSeedName))
        {
            throw new InvalidOperationException("Seed name is required.");
        }

        if (string.IsNullOrWhiteSpace(seed.TableName))
        {
            throw new InvalidOperationException($"Seed '{originalSeedName}' must define a table name.");
        }

        var mode = ParseSeedMode(seed.Mode);

        if (seed.Rows.Count == 0)
        {
            throw new InvalidOperationException($"Seed '{originalSeedName}' must define at least one row.");
        }

        if (mode != "insert_all_if_table_empty" && seed.MatchColumns.Count == 0)
        {
            throw new InvalidOperationException($"Seed '{originalSeedName}' must define match columns.");
        }

        if (mode == "patch_existing_when_empty" && seed.PatchColumnsWhenEmpty.Count == 0)
        {
            throw new InvalidOperationException($"Seed '{originalSeedName}' must define patch columns for patch mode.");
        }

        var manifestTable = manifest.Tables.SingleOrDefault(
            table => table.TableName.Equals(seed.TableName, StringComparison.OrdinalIgnoreCase));
        HashSet<string>? validColumns = null;
        if (manifestTable != null)
        {
            var tableDocument = LoadTable(manifestTable);
            validColumns = tableDocument.Schema.Columns
                .Select(column => column.Name)
                .Append("id")
                .ToHashSet(StringComparer.OrdinalIgnoreCase);
        }

        foreach (var row in seed.Rows)
        {
            if (row.Values.Count == 0)
            {
                throw new InvalidOperationException($"Seed '{originalSeedName}' contains an empty row.");
            }

            foreach (var matchColumn in seed.MatchColumns)
            {
                if (!row.Values.ContainsKey(matchColumn))
                {
                    throw new InvalidOperationException(
                        $"Seed '{originalSeedName}' is missing match column '{matchColumn}' in one or more rows.");
                }
            }

            foreach (var lookup in row.Lookups)
            {
                if (string.IsNullOrWhiteSpace(lookup.TargetColumn) ||
                    string.IsNullOrWhiteSpace(lookup.LookupTable) ||
                    string.IsNullOrWhiteSpace(lookup.LookupColumn) ||
                    string.IsNullOrWhiteSpace(lookup.SelectColumn))
                {
                    throw new InvalidOperationException($"Seed '{originalSeedName}' contains an incomplete lookup definition.");
                }
            }

            if (validColumns == null)
            {
                continue;
            }

            foreach (var key in row.Values.Keys)
            {
                if (!validColumns.Contains(key))
                {
                    throw new InvalidOperationException(
                        $"Seed '{originalSeedName}' references unknown column '{key}' for table '{seed.TableName}'.");
                }
            }

            foreach (var lookup in row.Lookups)
            {
                if (!validColumns.Contains(lookup.TargetColumn))
                {
                    throw new InvalidOperationException(
                        $"Seed '{originalSeedName}' references unknown target column '{lookup.TargetColumn}'.");
                }
            }
        }
    }

    private static void ValidateSeedManifestEntry(
        SchemaManifest manifest,
        string originalSeedName,
        SeedManifestEntry updatedEntry)
    {
        if (string.IsNullOrWhiteSpace(updatedEntry.SeedName))
        {
            throw new InvalidOperationException("Seed name is required.");
        }

        var duplicate = manifest.Seeds.Any(seed =>
            !seed.SeedName.Equals(originalSeedName, StringComparison.OrdinalIgnoreCase) &&
            seed.SeedName.Equals(updatedEntry.SeedName, StringComparison.OrdinalIgnoreCase));
        if (duplicate)
        {
            throw new InvalidOperationException($"Seed '{updatedEntry.SeedName}' already exists.");
        }
    }

    private static void ValidateIndexes(TableSchema table, IEnumerable<IndexSchema> indexes, string kind)
    {
        var seenNames = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        foreach (var index in indexes)
        {
            if (string.IsNullOrWhiteSpace(index.Name))
            {
                throw new InvalidOperationException($"Table '{table.TableName}' has a {kind} without a name.");
            }

            if (!seenNames.Add(index.Name))
            {
                throw new InvalidOperationException($"Table '{table.TableName}' contains duplicate {kind} '{index.Name}'.");
            }

            if (index.Columns.Count == 0)
            {
                throw new InvalidOperationException($"Table '{table.TableName}' {kind} '{index.Name}' must reference at least one column.");
            }

            foreach (var columnName in index.Columns)
            {
                if (!table.Columns.Any(column => column.Name.Equals(columnName, StringComparison.OrdinalIgnoreCase)))
                {
                    throw new InvalidOperationException(
                        $"Table '{table.TableName}' {kind} '{index.Name}' references unknown column '{columnName}'.");
                }
            }
        }
    }

    private static void ValidateForeignKeys(TableSchema table, HashSet<string> existingColumns)
    {
        var seenNames = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        foreach (var foreignKey in table.ForeignKeys)
        {
            if (string.IsNullOrWhiteSpace(foreignKey.Name))
            {
                throw new InvalidOperationException($"Table '{table.TableName}' has a foreign key without a name.");
            }

            if (!seenNames.Add(foreignKey.Name))
            {
                throw new InvalidOperationException(
                    $"Table '{table.TableName}' contains duplicate foreign key '{foreignKey.Name}'.");
            }

            if (foreignKey.Columns.Count == 0)
            {
                throw new InvalidOperationException(
                    $"Table '{table.TableName}' foreign key '{foreignKey.Name}' must reference at least one local column.");
            }

            if (string.IsNullOrWhiteSpace(foreignKey.ReferencedTable))
            {
                throw new InvalidOperationException(
                    $"Table '{table.TableName}' foreign key '{foreignKey.Name}' must define a referenced table.");
            }

            if (foreignKey.ReferencedColumns.Count == 0)
            {
                throw new InvalidOperationException(
                    $"Table '{table.TableName}' foreign key '{foreignKey.Name}' must reference at least one parent column.");
            }

            if (foreignKey.Columns.Count != foreignKey.ReferencedColumns.Count)
            {
                throw new InvalidOperationException(
                    $"Table '{table.TableName}' foreign key '{foreignKey.Name}' must have the same number of local and referenced columns.");
            }

            foreach (var columnName in foreignKey.Columns)
            {
                if (!existingColumns.Contains(columnName))
                {
                    throw new InvalidOperationException(
                        $"Table '{table.TableName}' foreign key '{foreignKey.Name}' references unknown local column '{columnName}'.");
                }
            }
        }
    }

    private static string SanitizeFileName(string tableName)
    {
        var invalid = Path.GetInvalidFileNameChars();
        var chars = tableName
            .Trim()
            .Select(ch => invalid.Contains(ch) || char.IsWhiteSpace(ch) ? '_' : ch)
            .ToArray();
        var result = new string(chars);
        return string.IsNullOrWhiteSpace(result) ? "new_table" : result.ToLowerInvariant();
    }

    private static TableManifestEntry CloneManifestEntry(TableManifestEntry entry)
    {
        return new TableManifestEntry
        {
            TableName = entry.TableName,
            File = entry.File,
            Version = entry.Version,
            VersionKeyName = entry.VersionKeyName
        };
    }

    private static SeedManifestEntry CloneManifestEntry(SeedManifestEntry entry)
    {
        return new SeedManifestEntry
        {
            SeedName = entry.SeedName,
            File = entry.File,
            Version = entry.Version,
            VersionKeyName = entry.VersionKeyName
        };
    }

    private static TableSchema CloneTableSchema(TableSchema table)
    {
        return new TableSchema
        {
            TableName = table.TableName,
            Columns = table.Columns.Select(column => new ColumnSchema
            {
                Name = column.Name,
                Type = column.Type,
                Nullable = column.Nullable,
                DefaultSql = column.DefaultSql,
                OnUpdateSql = column.OnUpdateSql
            }).ToList(),
            Indexes = table.Indexes.Select(index => new IndexSchema
            {
                Name = index.Name,
                Columns = [.. index.Columns]
            }).ToList(),
            UniqueIndexes = table.UniqueIndexes.Select(index => new IndexSchema
            {
                Name = index.Name,
                Columns = [.. index.Columns]
            }).ToList(),
            ForeignKeys = table.ForeignKeys.Select(foreignKey => new ForeignKeySchema
            {
                Name = foreignKey.Name,
                Columns = [.. foreignKey.Columns],
                ReferencedTable = foreignKey.ReferencedTable,
                ReferencedColumns = [.. foreignKey.ReferencedColumns],
                OnDelete = foreignKey.OnDelete,
                OnUpdate = foreignKey.OnUpdate
            }).ToList()
        };
    }

    private static SeedSchema CloneSeedSchema(SeedSchema seed)
    {
        return new SeedSchema
        {
            TableName = seed.TableName,
            Mode = seed.Mode,
            MatchColumns = [.. seed.MatchColumns],
            PatchColumnsWhenEmpty = [.. seed.PatchColumnsWhenEmpty],
            ZeroIsEmptyColumns = [.. seed.ZeroIsEmptyColumns],
            Rows = seed.Rows.Select(row => new SeedRow
            {
                Values = row.Values.ToDictionary(
                    pair => pair.Key,
                    pair => pair.Value.Clone(),
                    StringComparer.OrdinalIgnoreCase),
                Lookups = row.Lookups.Select(lookup => new SeedLookup
                {
                    TargetColumn = lookup.TargetColumn,
                    LookupTable = lookup.LookupTable,
                    LookupColumn = lookup.LookupColumn,
                    SelectColumn = lookup.SelectColumn,
                    LookupValue = lookup.LookupValue.Clone(),
                    Required = lookup.Required
                }).ToList()
            }).ToList()
        };
    }

    private static string ParseSeedMode(string mode)
    {
        return mode.Trim().ToLowerInvariant() switch
        {
            "ensure_missing_rows" => "ensure_missing_rows",
            "patch_existing_when_empty" => "patch_existing_when_empty",
            "insert_all_if_table_empty" => "insert_all_if_table_empty",
            _ => throw new InvalidOperationException($"Unsupported seed mode '{mode}'.")
        };
    }
}
