using System.ComponentModel;
using System.Diagnostics;
using DotPos.Shared.LocalDatabase;
using DotPos.Shared.LocalDatabase.Schema;
using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class MainForm : Form
{
    private static readonly string[] RecommendedColumnTypes =
    [
        "TEXT",
        "INT",
        "DATETIME",
        "DATE",
        "FLOAT",
        "DECIMAL(10,2)",
        "CHAR(36)",
        "VARCHAR(3)",
        "VARCHAR(32)",
        "VARCHAR(64)",
        "VARCHAR(128)",
        "VARCHAR(512)"
    ];

    private readonly SchemaEditorRepository _repository;
    private readonly SplitContainer _mainSplitContainer = new() { Dock = DockStyle.Fill };
    private readonly ListBox _tablesListBox = new() { Dock = DockStyle.Fill, DrawMode = DrawMode.OwnerDrawFixed };
    private readonly Label _schemaPathLabel = new() { AutoEllipsis = true, Dock = DockStyle.Fill };
    private readonly Label _tableNameLabel = new() { AutoSize = true };
    private readonly Label _versionLabel = new() { AutoSize = true };
    private readonly Label _tableFileLabel = new() { AutoEllipsis = true, Dock = DockStyle.Fill };
    private readonly Label _databaseTargetLabel = new() { AutoEllipsis = true, Dock = DockStyle.Fill };
    private readonly Label _statusLabel = new() { Dock = DockStyle.Fill, AutoEllipsis = true };
    private readonly TabControl _editorTabs = new() { Dock = DockStyle.Fill };
    private readonly DataGridView _columnsGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly DataGridView _indexesGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly DataGridView _uniqueIndexesGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly DataGridView _foreignKeysGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly LocalDbConnectionSettingsFileStore _dbSettingsStore = new();
    private static readonly string[] ForeignKeyActionOptions = ["", "RESTRICT", "CASCADE", "SET NULL", "NO ACTION"];

    private SchemaManifest _manifest;
    private TableDocument? _currentDocument;
    private BindingList<TableEditorRow> _rows = [];
    private BindingList<IndexEditorRow> _indexRows = [];
    private BindingList<IndexEditorRow> _uniqueIndexRows = [];
    private BindingList<ForeignKeyEditorRow> _foreignKeyRows = [];
    private readonly Dictionary<string, TableSchema> _draftTables = new(StringComparer.OrdinalIgnoreCase);
    private readonly HashSet<string> _dirtyTables = new(StringComparer.OrdinalIgnoreCase);
    private LocalDbConnectionSettings _dbSettings;

    public MainForm()
    {
        _repository = SchemaEditorRepository.CreateDefault();
        _manifest = _repository.LoadManifest();
        _dbSettings = _dbSettingsStore.LoadOrDefault();

        Text = "DOT POS Schema Editor";
        Width = 1200;
        Height = 760;
        StartPosition = FormStartPosition.CenterScreen;
        Shown += (_, _) => ApplyInitialLayout();
        Activated += (_, _) => ReloadDatabaseSettingsFromStore();
        FormClosing += MainForm_FormClosing;

        BuildLayout();
        LoadTables();
    }

    private void BuildLayout()
    {
        _mainSplitContainer.FixedPanel = FixedPanel.Panel1;
        _mainSplitContainer.Panel1MinSize = 160;
        _mainSplitContainer.SplitterWidth = 8;

        _mainSplitContainer.Panel1.Controls.Add(BuildTableListPanel());
        _mainSplitContainer.Panel2.Controls.Add(BuildEditorPanel());
        Controls.Add(_mainSplitContainer);
    }

    private void ApplyInitialLayout()
    {
        var desiredLeftWidth = Math.Max(210, Math.Min(250, ClientSize.Width / 6));
        if (_mainSplitContainer.SplitterDistance != desiredLeftWidth)
        {
            _mainSplitContainer.SplitterDistance = desiredLeftWidth;
        }
    }

    private Control BuildTableListPanel()
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 3,
            Padding = new Padding(10)
        };
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        var title = new Label
        {
            Text = "Tables",
            AutoSize = true,
            Font = new Font(Font, FontStyle.Bold)
        };

        _tablesListBox.SelectedIndexChanged += (_, _) => LoadSelectedTable();
        _tablesListBox.DrawItem += TablesListBox_DrawItem;

        var buttonPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            AutoSize = true
        };

        var addButton = new Button { Text = "Add Table", AutoSize = true };
        addButton.Click += (_, _) => AddTable();

        var deleteButton = new Button { Text = "Delete Table", AutoSize = true };
        deleteButton.Click += (_, _) => DeleteSelectedTable();

        var reloadButton = new Button { Text = "Reload", AutoSize = true };
        reloadButton.Click += (_, _) => ReloadManifest();

        buttonPanel.Controls.Add(addButton);
        buttonPanel.Controls.Add(deleteButton);
        buttonPanel.Controls.Add(reloadButton);

        panel.Controls.Add(title, 0, 0);
        panel.Controls.Add(_tablesListBox, 0, 1);
        panel.Controls.Add(buttonPanel, 0, 2);
        return panel;
    }

    private Control BuildEditorPanel()
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 4,
            Padding = new Padding(10)
        };
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        var header = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            AutoSize = true
        };
        header.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        header.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));

        header.Controls.Add(new Label { Text = "Schema Root:", AutoSize = true }, 0, 0);
        header.Controls.Add(_schemaPathLabel, 1, 0);
        header.Controls.Add(new Label { Text = "Table:", AutoSize = true }, 0, 1);
        header.Controls.Add(_tableNameLabel, 1, 1);
        header.Controls.Add(new Label { Text = "Version:", AutoSize = true }, 0, 2);
        header.Controls.Add(_versionLabel, 1, 2);
        header.Controls.Add(new Label { Text = "Table File:", AutoSize = true }, 0, 3);
        header.Controls.Add(_tableFileLabel, 1, 3);
        header.Controls.Add(new Label { Text = "Database:", AutoSize = true }, 0, 4);
        header.Controls.Add(_databaseTargetLabel, 1, 4);

        _schemaPathLabel.Text = _repository.SchemaRoot;
        UpdateDatabaseTargetLabel();

        ConfigureColumnsGrid();
        ConfigureIndexesGrid();
        ConfigureUniqueIndexesGrid();
        ConfigureForeignKeysGrid();
        ConfigureEditorTabs();

        var editorActionPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            AutoSize = true
        };

        var saveButton = new Button { Text = "Save Table", AutoSize = true };
        saveButton.Click += (_, _) => SaveCurrentTable();

        var databaseSettingsButton = new Button { Text = "Open DB Settings", AutoSize = true };
        databaseSettingsButton.Click += (_, _) => OpenDatabaseSettings();

        var manageSeedsButton = new Button { Text = "Manage Seeds", AutoSize = true };
        manageSeedsButton.Click += (_, _) => OpenSeedManager();

        var compareSchemaButton = new Button { Text = "Compare DB To Schema", AutoSize = true };
        compareSchemaButton.Click += async (_, _) => await CompareSchemaAsync();

        var applySchemaButton = new Button { Text = "Apply Schema", AutoSize = true };
        applySchemaButton.Click += async (_, _) => await ApplySchemaAsync();

        editorActionPanel.Controls.Add(saveButton);
        editorActionPanel.Controls.Add(databaseSettingsButton);
        editorActionPanel.Controls.Add(manageSeedsButton);
        editorActionPanel.Controls.Add(compareSchemaButton);
        editorActionPanel.Controls.Add(applySchemaButton);

        panel.Controls.Add(header, 0, 0);
        panel.Controls.Add(editorActionPanel, 0, 1);
        panel.Controls.Add(_editorTabs, 0, 2);
        panel.Controls.Add(_statusLabel, 0, 3);

        return panel;
    }

    private void ConfigureColumnsGrid()
    {
        _columnsGrid.AllowUserToAddRows = false;
        _columnsGrid.AllowUserToDeleteRows = false;
        _columnsGrid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        _columnsGrid.MultiSelect = false;
        _columnsGrid.CellValueChanged += (_, _) => UpdateCurrentDirtyState();
        _columnsGrid.EditingControlShowing += ColumnsGrid_EditingControlShowing;
        _columnsGrid.DataError += HandleGridDataError;
        _columnsGrid.CurrentCellDirtyStateChanged += (_, _) =>
        {
            if (_columnsGrid.IsCurrentCellDirty)
            {
                _columnsGrid.CommitEdit(DataGridViewDataErrorContexts.Commit);
            }
        };
        _columnsGrid.CellEndEdit += (_, _) => UpdateCurrentDirtyState();
        _columnsGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Name",
            DataPropertyName = nameof(TableEditorRow.Name),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 20
        });
        _columnsGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "Type",
            DataPropertyName = nameof(TableEditorRow.Type),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 22,
            DataSource = RecommendedColumnTypes,
            DisplayStyle = DataGridViewComboBoxDisplayStyle.ComboBox
        });
        _columnsGrid.Columns.Add(new DataGridViewCheckBoxColumn
        {
            HeaderText = "Nullable",
            DataPropertyName = nameof(TableEditorRow.Nullable),
            Width = 80
        });
        _columnsGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Default SQL",
            DataPropertyName = nameof(TableEditorRow.DefaultSql),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 28
        });
        _columnsGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "On Update SQL",
            DataPropertyName = nameof(TableEditorRow.OnUpdateSql),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 30
        });
    }

    private void ConfigureIndexesGrid()
    {
        ConfigureSimpleGrid(_indexesGrid);
        _indexesGrid.CellContentClick += IndexesGrid_CellContentClick;
        _indexesGrid.CellDoubleClick += IndexesGrid_CellDoubleClick;
        _indexesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Name",
            DataPropertyName = nameof(IndexEditorRow.Name),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 35
        });
        _indexesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Columns",
            DataPropertyName = nameof(IndexEditorRow.Columns),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 55,
            ReadOnly = true
        });
        _indexesGrid.Columns.Add(new DataGridViewButtonColumn
        {
            HeaderText = string.Empty,
            Text = "Select Columns",
            UseColumnTextForButtonValue = true,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells
        });
    }

    private void ConfigureUniqueIndexesGrid()
    {
        ConfigureSimpleGrid(_uniqueIndexesGrid);
        _uniqueIndexesGrid.CellContentClick += UniqueIndexesGrid_CellContentClick;
        _uniqueIndexesGrid.CellDoubleClick += UniqueIndexesGrid_CellDoubleClick;
        _uniqueIndexesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Name",
            DataPropertyName = nameof(IndexEditorRow.Name),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 35
        });
        _uniqueIndexesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Columns",
            DataPropertyName = nameof(IndexEditorRow.Columns),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 55,
            ReadOnly = true
        });
        _uniqueIndexesGrid.Columns.Add(new DataGridViewButtonColumn
        {
            HeaderText = string.Empty,
            Text = "Select Columns",
            UseColumnTextForButtonValue = true,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells
        });
    }

    private void ConfigureForeignKeysGrid()
    {
        ConfigureSimpleGrid(_foreignKeysGrid);
        _foreignKeysGrid.CellContentClick += ForeignKeysGrid_CellContentClick;
        _foreignKeysGrid.CellDoubleClick += ForeignKeysGrid_CellDoubleClick;
        _foreignKeysGrid.CellValueChanged += ForeignKeysGrid_CellValueChanged;
        _foreignKeysGrid.EditingControlShowing += ForeignKeysGrid_EditingControlShowing;
        _foreignKeysGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Name",
            DataPropertyName = nameof(ForeignKeyEditorRow.Name),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 20
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Columns",
            DataPropertyName = nameof(ForeignKeyEditorRow.Columns),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 14,
            ReadOnly = true
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewButtonColumn
        {
            HeaderText = string.Empty,
            Text = "Select Local Columns",
            UseColumnTextForButtonValue = true,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "Referenced Table",
            DataPropertyName = nameof(ForeignKeyEditorRow.ReferencedTable),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 16,
            DisplayStyle = DataGridViewComboBoxDisplayStyle.ComboBox
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Referenced Columns",
            DataPropertyName = nameof(ForeignKeyEditorRow.ReferencedColumns),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 14,
            ReadOnly = true
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewButtonColumn
        {
            HeaderText = string.Empty,
            Text = "Select Referenced Columns",
            UseColumnTextForButtonValue = true,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "On Delete",
            DataPropertyName = nameof(ForeignKeyEditorRow.OnDelete),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 14,
            DataSource = ForeignKeyActionOptions,
            DisplayStyle = DataGridViewComboBoxDisplayStyle.ComboBox
        });
        _foreignKeysGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "On Update",
            DataPropertyName = nameof(ForeignKeyEditorRow.OnUpdate),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 14,
            DataSource = ForeignKeyActionOptions,
            DisplayStyle = DataGridViewComboBoxDisplayStyle.ComboBox
        });
    }

    private void ConfigureSimpleGrid(DataGridView grid)
    {
        grid.AllowUserToAddRows = false;
        grid.AllowUserToDeleteRows = false;
        grid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        grid.MultiSelect = false;
        grid.DataError += HandleGridDataError;
        grid.CellValueChanged += (_, _) => UpdateCurrentDirtyState();
        grid.CurrentCellDirtyStateChanged += (_, _) =>
        {
            if (grid.IsCurrentCellDirty)
            {
                grid.CommitEdit(DataGridViewDataErrorContexts.Commit);
            }
        };
        grid.CellEndEdit += (_, _) => UpdateCurrentDirtyState();
    }

    private void ConfigureEditorTabs()
    {
        _editorTabs.TabPages.Add(BuildEditorTab(
            "Columns",
            "Add Column",
            "Remove Selected Column",
            _columnsGrid,
            () => _rows.Add(new TableEditorRow { Type = RecommendedColumnTypes[0] }),
            RemoveSelectedColumn));
        _editorTabs.TabPages.Add(BuildEditorTab(
            "Indexes",
            "Add Index",
            "Remove Selected Index",
            _indexesGrid,
            () => _indexRows.Add(new IndexEditorRow()),
            RemoveSelectedIndex));
        _editorTabs.TabPages.Add(BuildEditorTab(
            "Unique Indexes",
            "Add Unique Index",
            "Remove Selected Unique Index",
            _uniqueIndexesGrid,
            () => _uniqueIndexRows.Add(new IndexEditorRow()),
            RemoveSelectedUniqueIndex));
        _editorTabs.TabPages.Add(BuildEditorTab(
            "Foreign Keys",
            "Add Foreign Key",
            "Remove Selected Foreign Key",
            _foreignKeysGrid,
            () => _foreignKeyRows.Add(new ForeignKeyEditorRow()),
            RemoveSelectedForeignKey));
    }

    private TabPage BuildEditorTab(
        string title,
        string addButtonText,
        string removeButtonText,
        DataGridView grid,
        Action addAction,
        Action removeAction)
    {
        var page = new TabPage(title);
        var layout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2,
            Padding = new Padding(6)
        };
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        layout.RowStyles.Add(new RowStyle(SizeType.Percent, 100));

        var buttonPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            AutoSize = true
        };

        var addButton = new Button { Text = addButtonText, AutoSize = true };
        addButton.Click += (_, _) =>
        {
            addAction();
            UpdateCurrentDirtyState();
        };

        var removeButton = new Button { Text = removeButtonText, AutoSize = true };
        removeButton.Click += (_, _) => removeAction();

        buttonPanel.Controls.Add(addButton);
        buttonPanel.Controls.Add(removeButton);
        layout.Controls.Add(buttonPanel, 0, 0);
        layout.Controls.Add(grid, 0, 1);
        page.Controls.Add(layout);
        return page;
    }

    private void ColumnsGrid_EditingControlShowing(object? sender, DataGridViewEditingControlShowingEventArgs e)
    {
        if (_columnsGrid.CurrentCell?.OwningColumn.DataPropertyName == nameof(TableEditorRow.Type) &&
            e.Control is ComboBox comboBox)
        {
            comboBox.DropDownStyle = ComboBoxStyle.DropDownList;
        }
    }

    private void IndexesGrid_CellContentClick(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        if (_indexesGrid.Columns[e.ColumnIndex] is DataGridViewButtonColumn)
        {
            OpenIndexColumnSelector(e.RowIndex);
        }
    }

    private void IndexesGrid_CellDoubleClick(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        if (_indexesGrid.Columns[e.ColumnIndex].DataPropertyName == nameof(IndexEditorRow.Columns))
        {
            OpenIndexColumnSelector(e.RowIndex);
        }
    }

    private void UniqueIndexesGrid_CellContentClick(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        if (_uniqueIndexesGrid.Columns[e.ColumnIndex] is DataGridViewButtonColumn)
        {
            OpenUniqueIndexColumnSelector(e.RowIndex);
        }
    }

    private void UniqueIndexesGrid_CellDoubleClick(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        if (_uniqueIndexesGrid.Columns[e.ColumnIndex].DataPropertyName == nameof(IndexEditorRow.Columns))
        {
            OpenUniqueIndexColumnSelector(e.RowIndex);
        }
    }

    private void ForeignKeysGrid_EditingControlShowing(object? sender, DataGridViewEditingControlShowingEventArgs e)
    {
        var propertyName = _foreignKeysGrid.CurrentCell?.OwningColumn.DataPropertyName;
        if ((propertyName == nameof(ForeignKeyEditorRow.ReferencedTable) ||
             propertyName == nameof(ForeignKeyEditorRow.OnDelete) ||
             propertyName == nameof(ForeignKeyEditorRow.OnUpdate)) &&
            e.Control is ComboBox comboBox)
        {
            comboBox.DropDownStyle = ComboBoxStyle.DropDownList;
        }
    }

    private void ForeignKeysGrid_CellContentClick(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        if (_foreignKeysGrid.Columns[e.ColumnIndex] is not DataGridViewButtonColumn buttonColumn)
        {
            return;
        }

        if (buttonColumn.Text == "Select Local Columns")
        {
            OpenForeignKeyLocalColumnSelector(e.RowIndex);
            return;
        }

        if (buttonColumn.Text == "Select Referenced Columns")
        {
            OpenForeignKeyReferencedColumnSelector(e.RowIndex);
        }
    }

    private void ForeignKeysGrid_CellDoubleClick(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0)
        {
            return;
        }

        var propertyName = _foreignKeysGrid.Columns[e.ColumnIndex].DataPropertyName;
        if (propertyName == nameof(ForeignKeyEditorRow.Columns))
        {
            OpenForeignKeyLocalColumnSelector(e.RowIndex);
            return;
        }

        if (propertyName == nameof(ForeignKeyEditorRow.ReferencedColumns))
        {
            OpenForeignKeyReferencedColumnSelector(e.RowIndex);
        }
    }

    private void ForeignKeysGrid_CellValueChanged(object? sender, DataGridViewCellEventArgs e)
    {
        if (e.RowIndex < 0 || e.ColumnIndex < 0 || e.RowIndex >= _foreignKeyRows.Count)
        {
            return;
        }

        if (_foreignKeysGrid.Columns[e.ColumnIndex].DataPropertyName == nameof(ForeignKeyEditorRow.ReferencedTable))
        {
            _foreignKeyRows[e.RowIndex].ReferencedColumns = string.Empty;
            _foreignKeysGrid.Refresh();
        }
    }

    private void HandleGridDataError(object? sender, DataGridViewDataErrorEventArgs e)
    {
        e.ThrowException = false;
        SetStatus("Invalid cell value. Clear the field or pick a valid option.");
    }

    private void ReloadManifest()
    {
        PersistCurrentDraft();
        _manifest = _repository.LoadManifest();
        _currentDocument = null;
        _draftTables.Clear();
        _dirtyTables.Clear();
        LoadTables();
        SetStatus("Reloaded schema manifest.");
    }

    private void RefreshForeignKeyTableOptions()
    {
        if (_foreignKeysGrid.Columns
            .OfType<DataGridViewComboBoxColumn>()
            .FirstOrDefault(column => column.DataPropertyName == nameof(ForeignKeyEditorRow.ReferencedTable)) is { } tableColumn)
        {
            tableColumn.DataSource = new[] { string.Empty }.Concat(_repository.GetOrderedTables(_manifest)
                .Select(table => table.TableName)
                .OrderBy(tableName => tableName, StringComparer.OrdinalIgnoreCase))
                .ToList();
        }
    }

    private void LoadTables()
    {
        var selectedName = (_tablesListBox.SelectedItem as TableManifestEntry)?.TableName
            ?? _currentDocument?.ManifestEntry.TableName;

        RefreshForeignKeyTableOptions();

        _tablesListBox.BeginUpdate();
        _tablesListBox.DisplayMember = nameof(TableManifestEntry.TableName);
        _tablesListBox.ValueMember = nameof(TableManifestEntry.TableName);
        _tablesListBox.DataSource = _repository.GetOrderedTables(_manifest).ToList();
        _tablesListBox.EndUpdate();

        if (!string.IsNullOrWhiteSpace(selectedName))
        {
            for (var i = 0; i < _tablesListBox.Items.Count; i++)
            {
                if (_tablesListBox.Items[i] is TableManifestEntry table &&
                    table.TableName.Equals(selectedName, StringComparison.OrdinalIgnoreCase))
                {
                    _tablesListBox.SelectedIndex = i;
                    return;
                }
            }
        }

        if (_tablesListBox.Items.Count > 0)
        {
            _tablesListBox.SelectedIndex = 0;
        }
        else
        {
            BindTable(null);
        }
    }

    private void LoadSelectedTable()
    {
        PersistCurrentDraft();

        if (_tablesListBox.SelectedItem is not TableManifestEntry selected)
        {
            BindTable(null);
            return;
        }

        _currentDocument = _repository.LoadTable(selected);
        if (_draftTables.TryGetValue(selected.TableName, out var draft))
        {
            BindTable(_currentDocument, draft);
            return;
        }

        BindTable(_currentDocument, _currentDocument.Schema);
    }

    private void BindTable(TableDocument? document, TableSchema? visibleSchema = null)
    {
        _currentDocument = document;
        var schema = visibleSchema ?? document?.Schema;
        _tableNameLabel.Text = schema?.TableName ?? "(none)";
        _versionLabel.Text = document?.ManifestEntry.Version.ToString() ?? "-";
        _tableFileLabel.Text = document?.ManifestEntry.File ?? string.Empty;

        _rows = schema == null
            ? []
            : new BindingList<TableEditorRow>(
            schema.Columns.Select(column => new TableEditorRow
            {
                Name = column.Name,
                Type = column.Type,
                Nullable = column.Nullable,
                DefaultSql = column.DefaultSql ?? string.Empty,
                OnUpdateSql = column.OnUpdateSql ?? string.Empty
            }).ToList());
        _indexRows = schema == null
            ? []
            : new BindingList<IndexEditorRow>(
            schema.Indexes.Select(index => new IndexEditorRow
            {
                Name = index.Name,
                Columns = string.Join(", ", index.Columns)
            }).ToList());
        _uniqueIndexRows = schema == null
            ? []
            : new BindingList<IndexEditorRow>(
            schema.UniqueIndexes.Select(index => new IndexEditorRow
            {
                Name = index.Name,
                Columns = string.Join(", ", index.Columns)
            }).ToList());
        _foreignKeyRows = schema == null
            ? []
            : new BindingList<ForeignKeyEditorRow>(
            schema.ForeignKeys.Select(foreignKey => new ForeignKeyEditorRow
            {
                Name = foreignKey.Name,
                Columns = string.Join(", ", foreignKey.Columns),
                ReferencedTable = foreignKey.ReferencedTable,
                ReferencedColumns = string.Join(", ", foreignKey.ReferencedColumns),
                OnDelete = foreignKey.OnDelete ?? string.Empty,
                OnUpdate = foreignKey.OnUpdate ?? string.Empty
            }).ToList());
        _columnsGrid.DataSource = _rows;
        _indexesGrid.DataSource = _indexRows;
        _uniqueIndexesGrid.DataSource = _uniqueIndexRows;
        _foreignKeysGrid.DataSource = _foreignKeyRows;
        UpdateCurrentDirtyState();
    }

    private void AddTable()
    {
        var tableName = TextPromptDialog.Show(this, "Add Table", "Table name:");
        if (string.IsNullOrWhiteSpace(tableName))
        {
            return;
        }

        try
        {
            _repository.AddTable(_manifest, tableName);
            LoadTables();
            SelectTable(tableName);
            SetStatus($"Added table '{tableName}' with a placeholder column. Edit it and save to version the table.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void DeleteSelectedTable()
    {
        if (_tablesListBox.SelectedItem is not TableManifestEntry selected)
        {
            return;
        }

        var result = MessageBox.Show(
            this,
            $"Delete table '{selected.TableName}' and remove its JSON file?",
            "Delete Table",
            MessageBoxButtons.YesNo,
            MessageBoxIcon.Warning);
        if (result != DialogResult.Yes)
        {
            return;
        }

        try
        {
            _draftTables.Remove(selected.TableName);
            _dirtyTables.Remove(selected.TableName);
            _repository.DeleteTable(_manifest, selected);
            _currentDocument = null;
            LoadTables();
            SetStatus($"Deleted table '{selected.TableName}'.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void RemoveSelectedColumn()
    {
        if (_columnsGrid.CurrentRow?.DataBoundItem is not TableEditorRow row)
        {
            return;
        }

        _rows.Remove(row);
        UpdateCurrentDirtyState();
    }

    private void RemoveSelectedIndex()
    {
        if (_indexesGrid.CurrentRow?.DataBoundItem is not IndexEditorRow row)
        {
            return;
        }

        _indexRows.Remove(row);
        UpdateCurrentDirtyState();
    }

    private void RemoveSelectedUniqueIndex()
    {
        if (_uniqueIndexesGrid.CurrentRow?.DataBoundItem is not IndexEditorRow row)
        {
            return;
        }

        _uniqueIndexRows.Remove(row);
        UpdateCurrentDirtyState();
    }

    private void RemoveSelectedForeignKey()
    {
        if (_foreignKeysGrid.CurrentRow?.DataBoundItem is not ForeignKeyEditorRow row)
        {
            return;
        }

        _foreignKeyRows.Remove(row);
        UpdateCurrentDirtyState();
    }

    private void SaveCurrentTable()
    {
        if (_currentDocument == null)
        {
            return;
        }

        try
        {
            EndEditingAllGrids();
            var updated = BuildWorkingTableSchema();
            var result = _repository.SaveTable(_manifest, _currentDocument, updated);
            _currentDocument = _repository.LoadTable(_currentDocument.ManifestEntry);
            _draftTables.Remove(_currentDocument.ManifestEntry.TableName);
            _dirtyTables.Remove(_currentDocument.ManifestEntry.TableName);
            LoadTables();
            SelectTable(_currentDocument.Schema.TableName);
            SetStatus(
                result.TableChanged
                    ? $"Saved '{updated.TableName}'. Version increased to {result.NewVersion}."
                    : $"No schema changes detected for '{updated.TableName}'. Version remains {result.NewVersion}.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void OpenDatabaseSettings()
    {
        ReloadDatabaseSettingsFromStore();

        var executablePath = ResolveLocalDbConfigExecutablePath();
        if (executablePath == null)
        {
            ShowError("Could not locate DotPos.LocalDbConfig.exe. Build the DotPos.LocalDbConfig project and try again.");
            return;
        }

        try
        {
            Process.Start(new ProcessStartInfo
            {
                FileName = executablePath,
                UseShellExecute = true,
                WorkingDirectory = Path.GetDirectoryName(executablePath)
            });
            SetStatus("Opened Local DB Settings.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void OpenSeedManager()
    {
        PersistCurrentDraft();
        var selectedTableName = (_tablesListBox.SelectedItem as TableManifestEntry)?.TableName
            ?? _currentDocument?.ManifestEntry.TableName;

        using var dialog = new SeedManagerDialog(_repository);
        dialog.ShowDialog(this);

        _manifest = _repository.LoadManifest();
        LoadTables();
        if (!string.IsNullOrWhiteSpace(selectedTableName))
        {
            SelectTable(selectedTableName);
        }
    }

    private async Task ApplySchemaAsync()
    {
        ReloadDatabaseSettingsFromStore();
        PersistCurrentDraft();
        if (_dirtyTables.Count > 0)
        {
            var saveResult = MessageBox.Show(
                this,
                "Schema changes must be saved before they can be applied to the database. Save now?",
                "Apply Schema",
                MessageBoxButtons.OKCancel,
                MessageBoxIcon.Warning);
            if (saveResult == DialogResult.Cancel)
            {
                return;
            }

            if (!TrySaveAllDirtyTables())
            {
                return;
            }
        }

        Enabled = false;
        UseWaitCursor = true;
        try
        {
            SetStatus($"Applying schema to {_dbSettings.DisplayName}...");
            var applier = new LocalDatabaseSchemaApplier(HandleApplyMessage, HandleSchemaDriftDecision);
            var result = await applier.ApplyAsync(_repository.SchemaRoot, _dbSettings, CancellationToken.None);
            if (result.HasSkippedDrifts)
            {
                SetStatus($"Schema apply completed with skipped drift updates for {_dbSettings.DisplayName}.");
                MessageBox.Show(
                    this,
                    $"Schema apply completed for {_dbSettings.DisplayName}, but one or more drift updates were skipped.",
                    "Schema Editor",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
            }
            else
            {
                SetStatus($"Applied schema to {_dbSettings.DisplayName}.");
                MessageBox.Show(
                    this,
                    $"Schema applied to {_dbSettings.DisplayName}.",
                    "Schema Editor",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
            }
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
        finally
        {
            UseWaitCursor = false;
            Enabled = true;
        }
    }

    private async Task CompareSchemaAsync()
    {
        ReloadDatabaseSettingsFromStore();
        try
        {
            var applier = new LocalDatabaseSchemaApplier(HandleApplyMessage);
            var report = await applier.CompareAsync(_repository.SchemaRoot, _dbSettings, CancellationToken.None);
            using var dialog = new SchemaComparisonDialog(_repository.SchemaRoot, _dbSettings, report, HandleApplyMessage);
            dialog.ShowDialog(this);
            ReloadDatabaseSettingsFromStore();
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void PersistCurrentDraft()
    {
        if (_currentDocument == null)
        {
            return;
        }

        EndEditingAllGrids();
        var workingSchema = BuildWorkingTableSchema();
        var tableName = _currentDocument.ManifestEntry.TableName;
        if (_repository.IsTableChanged(_currentDocument, workingSchema))
        {
            _draftTables[tableName] = workingSchema;
            _dirtyTables.Add(tableName);
        }
        else
        {
            _draftTables.Remove(tableName);
            _dirtyTables.Remove(tableName);
        }

        _tablesListBox.Invalidate();
    }

    private void UpdateCurrentDirtyState()
    {
        if (_currentDocument == null)
        {
            return;
        }

        var workingSchema = BuildWorkingTableSchema();
        var tableName = _currentDocument.ManifestEntry.TableName;
        if (_repository.IsTableChanged(_currentDocument, workingSchema))
        {
            _draftTables[tableName] = workingSchema;
            _dirtyTables.Add(tableName);
            _tableNameLabel.Text = $"{workingSchema.TableName} *";
        }
        else
        {
            _draftTables.Remove(tableName);
            _dirtyTables.Remove(tableName);
            _tableNameLabel.Text = workingSchema.TableName;
        }

        _tablesListBox.Invalidate();
    }

    private TableSchema BuildWorkingTableSchema()
    {
        if (_currentDocument == null)
        {
            throw new InvalidOperationException("No table is currently loaded.");
        }

        return new TableSchema
        {
            TableName = _currentDocument.Schema.TableName,
            Columns = _rows
                .Where(row => !string.IsNullOrWhiteSpace(row.Name) || !string.IsNullOrWhiteSpace(row.Type))
                .Select(row => new ColumnSchema
                {
                    Name = SafeTrim(row.Name),
                    Type = SafeTrim(row.Type),
                    Nullable = row.Nullable,
                    DefaultSql = NormalizeOptional(row.DefaultSql),
                    OnUpdateSql = NormalizeOptional(row.OnUpdateSql)
                })
                .ToList(),
            Indexes = _indexRows
                .Where(row => !IsBlankIndexRow(row))
                .Select(row => new IndexSchema
                {
                    Name = SafeTrim(row.Name),
                    Columns = ParseColumnList(row.Columns)
                })
                .ToList(),
            UniqueIndexes = _uniqueIndexRows
                .Where(row => !IsBlankIndexRow(row))
                .Select(row => new IndexSchema
                {
                    Name = SafeTrim(row.Name),
                    Columns = ParseColumnList(row.Columns)
                })
                .ToList(),
            ForeignKeys = _foreignKeyRows
                .Where(row => !IsBlankForeignKeyRow(row))
                .Select(row => new ForeignKeySchema
                {
                    Name = SafeTrim(row.Name),
                    Columns = ParseColumnList(row.Columns),
                    ReferencedTable = SafeTrim(row.ReferencedTable),
                    ReferencedColumns = ParseColumnList(row.ReferencedColumns),
                    OnDelete = NormalizeOptional(row.OnDelete),
                    OnUpdate = NormalizeOptional(row.OnUpdate)
                })
                .ToList()
        };
    }

    private void MainForm_FormClosing(object? sender, FormClosingEventArgs e)
    {
        PersistCurrentDraft();
        if (_dirtyTables.Count == 0)
        {
            return;
        }

        var prompt = _dirtyTables.Count == 1
            ? $"You have unsaved changes in '{_dirtyTables.First()}'. Save before exiting?"
            : $"You have unsaved changes in {_dirtyTables.Count} tables. Save before exiting?";
        var result = MessageBox.Show(
            this,
            prompt,
            "Unsaved Changes",
            MessageBoxButtons.YesNoCancel,
            MessageBoxIcon.Warning);

        if (result == DialogResult.Cancel)
        {
            e.Cancel = true;
            return;
        }

        if (result == DialogResult.No)
        {
            return;
        }

        if (!TrySaveAllDirtyTables())
        {
            e.Cancel = true;
        }
    }

    private bool TrySaveAllDirtyTables()
    {
        PersistCurrentDraft();
        var selectedTableName = (_tablesListBox.SelectedItem as TableManifestEntry)?.TableName
            ?? _currentDocument?.ManifestEntry.TableName;
        var dirtyTableNames = _dirtyTables.ToList();
        foreach (var tableName in dirtyTableNames)
        {
            if (!_draftTables.TryGetValue(tableName, out var draft))
            {
                continue;
            }

            var manifestEntry = _manifest.Tables.SingleOrDefault(
                table => table.TableName.Equals(tableName, StringComparison.OrdinalIgnoreCase));
            if (manifestEntry == null)
            {
                ShowError($"Could not find manifest entry for dirty table '{tableName}'.");
                return false;
            }

            try
            {
                var originalDocument = _repository.LoadTable(manifestEntry);
                _repository.SaveTable(_manifest, originalDocument, draft);
                _draftTables.Remove(tableName);
                _dirtyTables.Remove(tableName);
            }
            catch (Exception ex)
            {
                ShowError(ex.Message);
                return false;
            }
        }

        _manifest = _repository.LoadManifest();
        LoadTables();
        if (!string.IsNullOrWhiteSpace(selectedTableName))
        {
            SelectTable(selectedTableName);
        }

        return true;
    }

    private void TablesListBox_DrawItem(object? sender, DrawItemEventArgs e)
    {
        e.DrawBackground();
        if (e.Index < 0 || e.Index >= _tablesListBox.Items.Count)
        {
            return;
        }

        if (_tablesListBox.Items[e.Index] is not TableManifestEntry table)
        {
            return;
        }

        var isDirty = _dirtyTables.Contains(table.TableName);
        var text = isDirty ? $"{table.TableName} *" : table.TableName;
        var color = isDirty ? Color.DarkOrange : e.ForeColor;
        TextRenderer.DrawText(
            e.Graphics,
            text,
            e.Font,
            e.Bounds,
            color,
            TextFormatFlags.Left | TextFormatFlags.VerticalCenter);
        e.DrawFocusRectangle();
    }

    private void SelectTable(string tableName)
    {
        for (var i = 0; i < _tablesListBox.Items.Count; i++)
        {
            if (_tablesListBox.Items[i] is TableManifestEntry table &&
                table.TableName.Equals(tableName, StringComparison.OrdinalIgnoreCase))
            {
                _tablesListBox.SelectedIndex = i;
                return;
            }
        }
    }

    private void UpdateDatabaseTargetLabel()
    {
        _databaseTargetLabel.Text = _dbSettings.DisplayName;
    }

    private void ReloadDatabaseSettingsFromStore()
    {
        _dbSettings = _dbSettingsStore.LoadOrDefault();
        UpdateDatabaseTargetLabel();
    }

    private void HandleApplyMessage(LocalDatabaseApplyMessage message)
    {
        if (InvokeRequired)
        {
            BeginInvoke(new Action(() => HandleApplyMessage(message)));
            return;
        }

        SetStatus(message.Message);
    }

    private LocalDatabaseSchemaDriftDecision HandleSchemaDriftDecision(LocalDatabaseSchemaDrift drift)
    {
        if (InvokeRequired)
        {
            return (LocalDatabaseSchemaDriftDecision)Invoke(
                new Func<LocalDatabaseSchemaDrift, LocalDatabaseSchemaDriftDecision>(HandleSchemaDriftDecision),
                drift)!;
        }

        return SchemaDriftDialog.Show(this, drift);
    }

    private void SetStatus(string message)
    {
        _statusLabel.Text = message;
    }

    private void ShowError(string message)
    {
        MessageBox.Show(this, message, "Schema Editor", MessageBoxButtons.OK, MessageBoxIcon.Error);
        SetStatus(message);
    }

    private void EndEditingAllGrids()
    {
        _columnsGrid.EndEdit();
        _indexesGrid.EndEdit();
        _uniqueIndexesGrid.EndEdit();
        _foreignKeysGrid.EndEdit();
    }

    private static bool IsBlankIndexRow(IndexEditorRow row)
    {
        return string.IsNullOrWhiteSpace(row.Name) &&
               string.IsNullOrWhiteSpace(row.Columns);
    }

    private static bool IsBlankForeignKeyRow(ForeignKeyEditorRow row)
    {
        return string.IsNullOrWhiteSpace(row.Name) &&
               string.IsNullOrWhiteSpace(row.Columns) &&
               string.IsNullOrWhiteSpace(row.ReferencedTable) &&
               string.IsNullOrWhiteSpace(row.ReferencedColumns) &&
               string.IsNullOrWhiteSpace(row.OnDelete) &&
               string.IsNullOrWhiteSpace(row.OnUpdate);
    }

    private static List<string> ParseColumnList(string value)
    {
        return (value ?? string.Empty)
            .Split(',', StringSplitOptions.TrimEntries | StringSplitOptions.RemoveEmptyEntries)
            .ToList();
    }

    private void OpenUniqueIndexColumnSelector(int rowIndex)
    {
        if (rowIndex < 0 || rowIndex >= _uniqueIndexRows.Count)
        {
            return;
        }

        var availableColumns = GetCurrentTableColumnNames();
        if (availableColumns.Count == 0)
        {
            ShowError("Add one or more columns to the table before selecting columns for a unique index.");
            return;
        }

        var row = _uniqueIndexRows[rowIndex];
        var selectedColumns = ParseColumnList(row.Columns);
        var updatedColumns = ColumnMultiSelectDialog.SelectColumns(
            this,
            "Select Unique Index Columns",
            availableColumns,
            selectedColumns,
            "Select one or more columns in index order.");
        if (updatedColumns == null)
        {
            return;
        }

        row.Columns = updatedColumns;
        _uniqueIndexesGrid.Refresh();
        UpdateCurrentDirtyState();
    }

    private void OpenIndexColumnSelector(int rowIndex)
    {
        if (rowIndex < 0 || rowIndex >= _indexRows.Count)
        {
            return;
        }

        var availableColumns = GetCurrentTableColumnNames();
        if (availableColumns.Count == 0)
        {
            ShowError("Add one or more columns to the table before selecting columns for an index.");
            return;
        }

        var row = _indexRows[rowIndex];
        var selectedColumns = ParseColumnList(row.Columns);
        var updatedColumns = ColumnMultiSelectDialog.SelectColumns(
            this,
            "Select Index Columns",
            availableColumns,
            selectedColumns,
            "Select one or more columns in index order.");
        if (updatedColumns == null)
        {
            return;
        }

        row.Columns = updatedColumns;
        _indexesGrid.Refresh();
        UpdateCurrentDirtyState();
    }

    private void OpenForeignKeyLocalColumnSelector(int rowIndex)
    {
        if (rowIndex < 0 || rowIndex >= _foreignKeyRows.Count)
        {
            return;
        }

        var availableColumns = GetCurrentTableColumnNames();
        if (availableColumns.Count == 0)
        {
            ShowError("Add one or more columns to the table before selecting foreign key columns.");
            return;
        }

        var row = _foreignKeyRows[rowIndex];
        var selectedColumns = ParseColumnList(row.Columns);
        var updatedColumns = ColumnMultiSelectDialog.SelectColumns(
            this,
            "Select Foreign Key Columns",
            availableColumns,
            selectedColumns,
            "Select one or more local columns in foreign key order.");
        if (updatedColumns == null)
        {
            return;
        }

        row.Columns = updatedColumns;
        if (ParseColumnList(row.ReferencedColumns).Count > ParseColumnList(updatedColumns).Count)
        {
            row.ReferencedColumns = string.Empty;
        }

        _foreignKeysGrid.Refresh();
        UpdateCurrentDirtyState();
    }

    private void OpenForeignKeyReferencedColumnSelector(int rowIndex)
    {
        if (rowIndex < 0 || rowIndex >= _foreignKeyRows.Count)
        {
            return;
        }

        var row = _foreignKeyRows[rowIndex];
        var referencedTable = SafeTrim(row.ReferencedTable);
        if (referencedTable.Length == 0)
        {
            ShowError("Choose a referenced table before selecting referenced columns.");
            return;
        }

        var availableColumns = GetTableColumnNames(referencedTable);
        if (availableColumns.Count == 0)
        {
            ShowError($"Table '{referencedTable}' does not have any selectable columns.");
            return;
        }

        var requiredCount = ParseColumnList(row.Columns).Count;
        if (requiredCount == 0)
        {
            ShowError("Select the local foreign key columns first.");
            return;
        }

        var selectedColumns = ParseColumnList(row.ReferencedColumns);
        var updatedColumns = ColumnMultiSelectDialog.SelectColumns(
            this,
            "Select Referenced Columns",
            availableColumns,
            selectedColumns,
            $"Select exactly {requiredCount} referenced column{(requiredCount == 1 ? string.Empty : "s")} in matching order.",
            requiredCount);
        if (updatedColumns == null)
        {
            return;
        }

        row.ReferencedColumns = updatedColumns;
        _foreignKeysGrid.Refresh();
        UpdateCurrentDirtyState();
    }

    private List<string> GetCurrentTableColumnNames()
    {
        return _rows
            .Where(row => !string.IsNullOrWhiteSpace(row.Name))
            .Select(row => SafeTrim(row.Name))
            .Where(name => name.Length > 0)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .OrderBy(name => name, StringComparer.OrdinalIgnoreCase)
            .ToList();
    }

    private List<string> GetTableColumnNames(string tableName)
    {
        if (_currentDocument != null &&
            _currentDocument.Schema.TableName.Equals(tableName, StringComparison.OrdinalIgnoreCase))
        {
            return GetCurrentTableColumnNames();
        }

        var manifestEntry = _manifest.Tables.SingleOrDefault(
            table => table.TableName.Equals(tableName, StringComparison.OrdinalIgnoreCase));
        if (manifestEntry == null)
        {
            return [];
        }

        var document = _repository.LoadTable(manifestEntry);
        return document.Schema.Columns
            .Where(column => !string.IsNullOrWhiteSpace(column.Name))
            .Select(column => SafeTrim(column.Name))
            .Where(name => name.Length > 0)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .OrderBy(name => name, StringComparer.OrdinalIgnoreCase)
            .ToList();
    }

    private static string SafeTrim(string? value)
    {
        return value?.Trim() ?? string.Empty;
    }

    private static string? NormalizeOptional(string? value)
    {
        var trimmed = SafeTrim(value);
        return trimmed.Length == 0 ? null : trimmed;
    }

    private static string? ResolveLocalDbConfigExecutablePath()
    {
        var currentDirectory = new DirectoryInfo(AppContext.BaseDirectory);
        var sideBySidePath = Path.Combine(currentDirectory.FullName, "DotPos.LocalDbConfig.exe");
        if (File.Exists(sideBySidePath))
        {
            return sideBySidePath;
        }

        var targetFramework = currentDirectory.Name;
        var configuration = currentDirectory.Parent?.Name;
        var root = currentDirectory;
        while (root != null)
        {
            if (File.Exists(Path.Combine(root.FullName, "DotPos.sln")))
            {
                var candidateProjectDirectories = new[]
                {
                    Path.Combine(root.FullName, "tools", "localdb-config", "DotPos.LocalDbConfig"),
                    Path.Combine(root.FullName, "DotPos.LocalDbConfig")
                };

                foreach (var projectDirectory in candidateProjectDirectories)
                {
                    if (!Directory.Exists(projectDirectory))
                    {
                        continue;
                    }

                    if (!string.IsNullOrWhiteSpace(configuration))
                    {
                        var buildOutputPath = Path.Combine(
                            projectDirectory,
                            "bin",
                            configuration,
                            targetFramework,
                            "DotPos.LocalDbConfig.exe");
                        if (File.Exists(buildOutputPath))
                        {
                            return buildOutputPath;
                        }
                    }

                    var fallbackDirectory = Path.Combine(projectDirectory, "bin");
                    if (!Directory.Exists(fallbackDirectory))
                    {
                        continue;
                    }

                    var fallbackPath = Directory
                        .EnumerateFiles(fallbackDirectory, "DotPos.LocalDbConfig.exe", SearchOption.AllDirectories)
                        .Select(path => new FileInfo(path))
                        .OrderByDescending(file => file.LastWriteTimeUtc)
                        .ThenByDescending(file => file.FullName, StringComparer.OrdinalIgnoreCase)
                        .Select(file => file.FullName)
                        .FirstOrDefault();
                    if (fallbackPath != null)
                    {
                        return fallbackPath;
                    }
                }

                break;
            }

            root = root.Parent;
        }

        return null;
    }
}
