using DotPos.Shared.LocalDatabase.Schema;
using System.Drawing;
using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class SchemaDriftDialog : Form
{
    private SchemaDriftDialog(LocalDatabaseSchemaDrift drift)
    {
        Text = "Schema Drift Detected";
        FormBorderStyle = FormBorderStyle.FixedDialog;
        StartPosition = FormStartPosition.CenterParent;
        MinimizeBox = false;
        MaximizeBox = false;
        ShowInTaskbar = false;
        Width = 720;
        Height = 260;

        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            RowCount = 3,
            Padding = new Padding(12)
        };
        panel.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        panel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));

        var iconBox = new PictureBox
        {
            SizeMode = PictureBoxSizeMode.AutoSize,
            Image = SystemIcons.Warning.ToBitmap(),
            Margin = new Padding(0, 4, 12, 0)
        };

        var messageLabel = new Label
        {
            Dock = DockStyle.Fill,
            AutoSize = true,
            Text = $"Table: {drift.TableName}{Environment.NewLine}{Environment.NewLine}{drift.Message}{Environment.NewLine}{Environment.NewLine}Choose how to continue:",
            MaximumSize = new Size(620, 0)
        };

        var buttonPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.RightToLeft,
            AutoSize = true
        };

        var cancelButton = new Button
        {
            Text = "Cancel",
            DialogResult = DialogResult.Cancel,
            AutoSize = true
        };

        var continueButton = new Button
        {
            Text = "Continue Without Updating",
            DialogResult = DialogResult.No,
            AutoSize = true
        };

        var updateButton = new Button
        {
            Text = "Update To Schema",
            DialogResult = DialogResult.Yes,
            AutoSize = true
        };

        buttonPanel.Controls.Add(cancelButton);
        buttonPanel.Controls.Add(continueButton);
        buttonPanel.Controls.Add(updateButton);

        panel.Controls.Add(iconBox, 0, 0);
        panel.Controls.Add(messageLabel, 1, 0);
        panel.Controls.Add(buttonPanel, 0, 2);
        panel.SetColumnSpan(buttonPanel, 2);

        Controls.Add(panel);
        AcceptButton = updateButton;
        CancelButton = cancelButton;
    }

    public static LocalDatabaseSchemaDriftDecision Show(IWin32Window owner, LocalDatabaseSchemaDrift drift)
    {
        using var dialog = new SchemaDriftDialog(drift);
        return dialog.ShowDialog(owner) switch
        {
            DialogResult.Yes => LocalDatabaseSchemaDriftDecision.UpdateToSchema,
            DialogResult.No => LocalDatabaseSchemaDriftDecision.ContinueWithoutUpdating,
            _ => LocalDatabaseSchemaDriftDecision.CancelApply
        };
    }
}
